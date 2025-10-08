<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief WebhookCall class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Webhook\UseCase;

use MaarchCourrier\SignatureBook\Application\Webhook\RetrieveSignedResource;
use MaarchCourrier\SignatureBook\Application\Webhook\StoreSignedResource;
use MaarchCourrier\SignatureBook\Application\Webhook\WebhookValidation;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureHistoryServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\AttachmentOutOfPerimeterProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\NoEncodedContentRetrievedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceAlreadySignProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceIdEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceIdMasterNotCorrespondingProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\RetrieveDocumentUrlEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\StoreResourceProblem;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;

class WebhookCall
{
    /**
     * @param WebhookValidation $webhookValidation
     * @param RetrieveSignedResource $retrieveSignedResource
     * @param StoreSignedResource $storeSignedResource
     * @param SignatureHistoryServiceInterface $historyService
     */
    public function __construct(
        private readonly WebhookValidation $webhookValidation,
        private readonly RetrieveSignedResource $retrieveSignedResource,
        private readonly StoreSignedResource $storeSignedResource,
        private readonly SignatureHistoryServiceInterface $historyService
    ) {
    }


    /**
     * @param array $body
     * @param array $decodedToken
     * @return int|array
     * @throws AttachmentOutOfPerimeterProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceAlreadySignProblem
     * @throws ResourceIdEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     * @throws StoreResourceProblem
     * @throws UserDoesNotExistProblem
     * @throws NoEncodedContentRetrievedProblem
     */
    public function execute(array $body, array $decodedToken): int|array
    {
        $signedResource = $this->webhookValidation->validateAndCreateResource($body, $decodedToken);

        $signedResource = $this->retrieveSignedResource->retrieveSignedResourceContent(
            $signedResource,
            $body['retrieveDocUri']
        );

        switch ($signedResource->getStatus()) {
            case 'VAL':
                $id = $this->storeSignedResource->store($signedResource);

                $this->historyService->historySignatureValidation(
                    $signedResource->getResIdSigned(),
                    $signedResource->getResIdMaster()
                );

                return $id;
            case 'REF':
                $this->historyService->historySignatureRefus(
                    $signedResource->getResIdSigned(),
                    $signedResource->getResIdMaster()
                );
                break;
            case 'ERROR':
                $this->historyService->historySignatureError(
                    $signedResource->getResIdSigned(),
                    $signedResource->getResIdMaster()
                );
                break;
            default:
                break;
        }

        $result = ['message' => 'Status of signature is ' . $signedResource->getStatus()];

        if (!empty($signedResource->getMessageStatus())) {
            $result['message'] .= " : " . $signedResource->getMessageStatus();
        }
        return $result;
    }
}
