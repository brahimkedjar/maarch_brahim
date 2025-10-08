<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief RetrieveSignatureBook class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application;

use MaarchCourrier\Authorization\Domain\Problem\MainResourceOutOfPerimeterProblem;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourcePerimeterCheckerInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\DocumentConversion\Domain\Port\ConvertPdfServiceInterface;
use MaarchCourrier\DocumentConversion\Domain\Port\SignatureMainDocumentRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Privilege\SignDocumentPrivilege;
use MaarchCourrier\SignatureBook\Domain\Problem\MainResourceDoesNotExistInSignatureBookBasketProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBook;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;

class RetrieveSignatureBook
{
    public function __construct(
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly CurrentUserInterface $currentUser,
        private readonly MainResourcePerimeterCheckerInterface $mainResourceAccessControl,
        private readonly SignatureBookRepositoryInterface $signatureBookRepository,
        private readonly SignatureMainDocumentRepositoryInterface $signatureMainDocument,
        private readonly ConvertPdfServiceInterface $convertPdfService,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly PrivilegeCheckerInterface $privilegeChecker,
        private readonly VisaWorkflowRepositoryInterface $visaWorkflowRepository,
    ) {
    }

    /**
     * @param int $resId
     *
     * @return SignatureBook
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     */
    public function getSignatureBook(int $resId): SignatureBook
    {
        $resource = $this->mainResourceRepository->getMainResourceByResId($resId);
        if ($resource === null) {
            throw new ResourceDoesNotExistProblem();
        }

        $currentUser = $this->currentUser->getCurrentUser();
        if (!$this->mainResourceAccessControl->hasRightByResId($resource->getResId(), $currentUser)) {
            throw new MainResourceOutOfPerimeterProblem();
        }

        $isInSignatureBook = $this->signatureBookRepository
            ->isMainResourceInSignatureBookBasket($resource, $currentUser);
        if (empty($isInSignatureBook)) {
            throw new MainResourceDoesNotExistInSignatureBookBasketProblem();
        }

        $canUpdateDocuments = $this->signatureBookRepository
            ->canUpdateResourcesInSignatureBook($resource, $currentUser);

        $resourcesToSign = [];
        $resourcesAttached = [];

        $mainSignatureBookResource = SignatureBookResource::createFromMainResource($resource);

        if (!empty($resource->getFilename())) {
            $isConverted = $this->convertPdfService->canConvert($resource->getFileFormat());
            $mainSignatureBookResource->setIsConverted($isConverted);

            if ($resource->isInSignatureBook()) {
                $resourcesToSign[] = $mainSignatureBookResource;
            } else {
                $isCreator = $resource->getTypist()->getId() == $this->currentUser->getCurrentUser()->getId();
                $canModify = $canUpdateDocuments || $isCreator;

                $mainSignatureBookResource->setCanModify($canModify);
                $resourcesAttached[] = $mainSignatureBookResource;
            }
        }

        $attachments = $this->attachmentRepository->getAttachmentsInSignatureBookByMainResource($resource);
        foreach ($attachments as $attachment) {
            $isConverted = $this->convertPdfService->canConvert($attachment->getFileFormat());

            if ($attachment->isSignable()) {
                $resourcesToSign[] = (SignatureBookResource::createFromAttachment($attachment))
                    ->setIsConverted($isConverted);
            } else {
                $isCreator = $attachment->getTypist()->getId() == $this->currentUser->getCurrentUser()->getId();
                $canModify = $canUpdateDocuments || $isCreator;
                $canDelete = $canModify;  //Deletion permission follows the same logic as modification permission.

                $resourcesAttached[] = (SignatureBookResource::createFromAttachment($attachment))
                    ->setIsConverted($isConverted)
                    ->setCanModify($canModify)
                    ->setCanDelete($canDelete);
            }
        }

        $canSignResources = $this->privilegeChecker->hasPrivilege($currentUser, new SignDocumentPrivilege());
        $hasActiveWorkflow = $this->visaWorkflowRepository->isWorkflowActiveByMainResource($resource);

        $isCurrentUserWorkflow = false;
        $currentWorkflowUser = $this->visaWorkflowRepository->getCurrentStepUserByMainResource($resource);
        if (!empty($currentWorkflowUser)) {
            $isCurrentUserWorkflow = $currentWorkflowUser->getId() === $currentUser->getId();
        }

        $signatureBook = new SignatureBook();
        $signatureBook->setResourcesToSign($resourcesToSign)
            ->setResourcesAttached($resourcesAttached)
            ->setCanSignResources($canSignResources)
            ->setCanUpdateResources($canUpdateDocuments)
            ->setHasActiveWorkflow($hasActiveWorkflow)
            ->setIsCurrentWorkflowUser($isCurrentUserWorkflow);

        return $signatureBook;
    }
}
