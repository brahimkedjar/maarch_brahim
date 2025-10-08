<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief WebhookValidation class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Webhook;

use DateTime;
use Exception;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\ResourceToSignRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\AttachmentOutOfPerimeterProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\IdParapheurIsMissingProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceIdEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceIdMasterNotCorrespondingProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\RetrieveDocumentUrlEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\SignedResource;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;

class WebhookValidation
{
    /**
     * @param ResourceToSignRepositoryInterface $resourceToSignRepository
     * @param UserRepositoryInterface $userRepository
     * @param CurrentUserInterface $currentUser
     */
    public function __construct(
        private readonly ResourceToSignRepositoryInterface $resourceToSignRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly CurrentUserInterface $currentUser
    ) {
    }

    /**
     * @param array $body
     * @param array $decodedToken
     * @return SignedResource
     * @throws AttachmentOutOfPerimeterProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceIdEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     * @throws UserDoesNotExistProblem
     * @throws Exception
     */
    public function validateAndCreateResource(array $body, array $decodedToken): SignedResource
    {
        if (empty($body['retrieveDocUri'])) {
            throw new RetrieveDocumentUrlEmptyProblem();
        }

        if (empty($body['token']) || !isset($decodedToken['userSerialId'])) {
            throw new CurrentTokenIsNotFoundProblem();
        }

        $currentUser = $this->userRepository->getUserById($decodedToken['userSerialId']);
        if ($currentUser === null) {
            throw new UserDoesNotExistProblem();
        }

        $this->currentUser->setCurrentUser($decodedToken['userSerialId']);

        if (!isset($decodedToken['resId'])) {
            throw new ResourceIdEmptyProblem();
        }

        $signedResource = new SignedResource();

        if (empty($body['payload']['idParapheur'])) {
            throw new IdParapheurIsMissingProblem();
        }

        $signedResource->setId($body['payload']['idParapheur']);
        $signedResource->setStatus($body['signatureState']['state']);

        if (!empty($body['signatureState']['message'])) {
            $signedResource->setMessageStatus(
                $body['signatureState']['message']
            );
        }

        if (!empty($body['signatureState']['error'])) {
            $signedResource->setMessageStatus(
                $body['signatureState']['error']
            );
        }

        if ($body['signatureState']['updatedDate'] !== null) {
            $signedResource->setSignatureDate(new DateTime($body['signatureState']['updatedDate']));
        }

        if (isset($decodedToken['resIdMaster'])) {
            if (
                !$this->resourceToSignRepository->checkConcordanceResIdAndResIdMaster(
                    $decodedToken['resId'],
                    $decodedToken['resIdMaster']
                )
            ) {
                throw new ResourceIdMasterNotCorrespondingProblem(
                    $decodedToken['resId'],
                    $decodedToken['resIdMaster']
                );
            }

            $infosAttachment = $this->resourceToSignRepository->getAttachmentInformations($decodedToken['resId']);

            if (empty($infosAttachment)) {
                throw new AttachmentOutOfPerimeterProblem();
            }

            $signedResource->setResIdMaster($decodedToken['resIdMaster']);
        }

        $signedResource->setResIdSigned($decodedToken['resId']);

        return $signedResource;
    }
}
