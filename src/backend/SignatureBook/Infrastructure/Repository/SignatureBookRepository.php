<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief  SignatureBookRepository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Repository;

use Attachment\models\AttachmentModel;
use Attachment\models\AttachmentTypeModel;
use Convert\controllers\ConvertPdfController;
use Entity\models\ListInstanceModel;
use Exception;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;
use Resource\Domain\Resource;
use SignatureBook\controllers\SignatureBookController;

class SignatureBookRepository implements SignatureBookRepositoryInterface
{
    /**
     * @param Resource $resource
     *
     * @return SignatureBookResource[]
     * @throws Exception
     */
    public function getIncomingMainResource(Resource $resource): array
    {
        $isConverted = ConvertPdfController::canConvert(['extension' => $resource->getFormat()]);

        $resourceToSign = (new SignatureBookResource())
            ->setResId($resource->getResId())
            ->setTitle($resource->getSubject())
            /**
             * TODO : Refacto Resource domain to replace property 'typist' of int by 'creator' of UserInterface
             */
            ->setCreatorId($resource->getTypist())
            ->setChrono($resource->getAltIdentifier())
            ->setType('main_document')
            ->setTypeLabel(_MAIN_DOCUMENT)
            ->setIsConverted($isConverted);

        return [$resourceToSign];
    }

    /**
     * @param Resource $resource
     *
     * @return SignatureBookResource[]
     * @throws Exception
     */
    public function getIncomingAttachments(Resource $resource): array
    {
        $resourcesToSign = [];

        $attachmentTypeLabel = AttachmentTypeModel::getByTypeId(['typeId' => 'incoming_mail_attachment']);
        $attachmentTypeLabel = $attachmentTypeLabel['label'];

        $incomingMailAttachments = AttachmentModel::get([
            'select' => [
                'res_id', 'res_id_master', 'title', 'identifier', 'relation', 'attachment_type', 'format', 'typist'
            ],
            'where'  => ['res_id_master = ?', 'attachment_type = ?', "status not in ('DEL', 'TMP', 'OBS')"],
            'data'   => [$resource->getResId(), 'incoming_mail_attachment']
        ]);

        foreach ($incomingMailAttachments as $value) {
            $isConverted = ConvertPdfController::canConvert(['extension' => $value['format']]);

            $resourceToSign = (new SignatureBookResource())
                ->setResId($value['res_id'])
                ->setResIdMaster($value['res_id_master'])
                ->setTitle($value['title'])
                ->setChrono($value['identifier'] ?? '')
                /**
                 * TODO : Refacto Resource domain to replace property 'typist' of int by 'creator' of UserInterface
                 */
                ->setCreatorId($value['typist'])
                ->setSignedResId($value['relation'])
                ->setType($value['attachment_type'])
                ->setTypeLabel($attachmentTypeLabel)
                ->setIsConverted($isConverted);
            $resourcesToSign[] = $resourceToSign;
        }

        return $resourcesToSign;
    }

    /**
     * @param Resource $resource
     *
     * @return SignatureBookResource[]
     * @throws Exception
     */
    public function getAttachments(Resource $resource): array
    {
        $resourcesAttached = [];

        $attachmentTypes = AttachmentTypeModel::get(['select' => ['type_id', 'label', 'icon', 'signable']]);
        $attachmentTypes = array_column($attachmentTypes, null, 'type_id');

        $orderBy = "CASE attachment_type WHEN 'response_project' THEN 1";
        $c = 2;
        foreach ($attachmentTypes as $value) {
            if ($value['signable'] && $value['type_id'] != 'response_project') {
                $orderBy .= " WHEN '{$value['type_id']}' THEN {$c}";
                ++$c;
            }
        }
        $orderBy .= " ELSE {$c} END, validation_date DESC NULLS LAST, creation_date DESC";

        $attachmentTypeLabel = '(select label from attachment_types where type_id = res_attachments.attachment_type) ';
        $attachmentTypeLabel .= 'as attachment_type_label';

        $attachments = AttachmentModel::get([
            'select'    => [
                'res_id', 'res_id_master', 'title', 'identifier', 'relation', $attachmentTypeLabel, 'attachment_type',
                'format', 'typist'
            ],
            'where'     => [
                'res_id_master = ?', 'attachment_type != ?', "status not in ('DEL', 'OBS')", 'in_signature_book = TRUE'
            ],
            'data'      => [$resource->getResId(), 'incoming_mail_attachment'],
            'orderBy'   => [$orderBy]
        ]);

        foreach ($attachments as $value) {
            $isConverted = ConvertPdfController::canConvert(['extension' => $value['format']]);

            $resourceAttached = new SignatureBookResource();
            $resourceAttached->setResId($value['res_id'])
                ->setResIdMaster($value['res_id_master'])
                ->setTitle($value['title'])
                ->setChrono($value['identifier'] ?? '')
                /**
                 * TODO : Refacto Resource domain to replace property 'typist' of int by 'creator' of UserInterface
                 */
                ->setCreatorId($value['typist'])
                ->setSignedResId($value['relation'])
                ->setType($value['attachment_type'])
                ->setTypeLabel($value['attachment_type_label'])
                ->setIsConverted($isConverted);
            $resourcesAttached[] = $resourceAttached;
        }


        return $resourcesAttached;
    }

    /**
     * @param MainResourceInterface $mainResource
     * @param UserInterface $user
     *
     * @return bool
     */
    public function canUpdateResourcesInSignatureBook(MainResourceInterface $mainResource, UserInterface $user): bool
    {
        return SignatureBookController::isResourceInSignatureBook([
            'resId' => $mainResource->getResId(),
            'userId' => $user->getId(),
            'canUpdateDocuments' => true
        ]);
    }

    /**
     * @param Resource $resource
     *
     * @return bool
     */
    public function doesMainResourceHasActiveWorkflow(Resource $resource): bool
    {
        $listInstances = ListInstanceModel::get([
            'select'    => ['COUNT(*)'],
            'where'     => ['res_id = ?', 'item_mode in (?)', 'process_date IS NULL'],
            'data'      => [$resource->getResId(), ['visa', 'sign']]
        ]);

        return ((int)$listInstances[0]['count'] > 0);
    }

    /**
     * @param Resource $resource
     *
     * @return ?int
     */
    public function getWorkflowUserIdByCurrentStep(Resource $resource): ?int
    {
        $currentStep = ListInstanceModel::getCurrentStepByResId(['resId' => $resource->getResId()]);
        return $currentStep['item_id'] ?? null;
    }

    public function isMainResourceInSignatureBookBasket(MainResourceInterface $mainResource, UserInterface $user): bool
    {
        return SignatureBookController::isResourceInSignatureBook([
            'resId' => $mainResource->getResId(),
            'userId' => $user->getId()
        ]);
    }
}
