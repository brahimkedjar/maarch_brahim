<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief continueCircuitAction class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Action;

use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\DataToBeSentToTheParapheurAreEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\NoDocumentsInSignatureBookForThisId;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureNotAppliedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;

class ContinueCircuitAction
{
    /**
     * @param CurrentUserInterface $currentUser
     * @param SignatureServiceInterface $signatureService
     * @param SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader
     * @param bool $isNewSignatureBookEnabled
     */
    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly SignatureServiceInterface $signatureService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader,
        private readonly bool $isNewSignatureBookEnabled
    ) {
    }

    /**
     * @param int $resId
     * @param array $data
     * @param array $note
     * @return bool
     * @throws CurrentTokenIsNotFoundProblem
     * @throws DataToBeSentToTheParapheurAreEmptyProblem
     * @throws NoDocumentsInSignatureBookForThisId
     * @throws SignatureBookNoConfigFoundProblem
     * @throws SignatureNotAppliedProblem
     */
    public function execute(int $resId, array $data, array $note): bool
    {
        if (!$this->isNewSignatureBookEnabled) {
            return true;
        }

        $signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $accessToken = $this->currentUser->getCurrentUserToken();
        if (empty($accessToken)) {
            throw new CurrentTokenIsNotFoundProblem();
        }

        if (isset($data[$resId])) {
            foreach ($data[$resId] as $document) {
                $missingData = [];

                if (!empty($data['digitalCertificate'])) {
                    $requiredData = [
                        'resId',
                        'documentId',
                        'hashSignature',
                        'certificate',
                        'signatureContentLength',
                        'signatureFieldName',
                        'cookieSession'
                    ];

                    foreach ($requiredData as $requiredDatum) {
                        if (empty($document[$requiredDatum])) {
                            $missingData[] = $requiredDatum;
                        }
                    }

                    if (!empty($missingData)) {
                        throw new DataToBeSentToTheParapheurAreEmptyProblem($missingData);
                    }

                    $document['documentId'] = intval($document['documentId'] ?? 0);

                    $resourceToSign = [
                        'resId' => $document['resId']
                    ];

                    if (isset($document['isAttachment']) && $document['isAttachment']) {
                        $resourceToSign['resIdMaster'] = $resId;
                    }

                    $applySuccess = $this->signatureService
                        ->setConfig($signatureBook)
                        ->applySignature(
                            $document['documentId'],
                            $document['hashSignature'],
                            $document['signatures'] ?? [],
                            $document['certificate'],
                            $document['signatureContentLength'],
                            $document['signatureFieldName'],
                            $document['tmpUniqueId'] ?? null,
                            $accessToken,
                            $document['cookieSession'],
                            $resourceToSign
                        );
                } else {
                    $requiredData = [
                        'resId',
                        'documentId'
                    ];

                    foreach ($requiredData as $requiredDatum) {
                        if (empty($document[$requiredDatum])) {
                            $missingData[] = $requiredDatum;
                        }
                    }

                    if (!empty($missingData)) {
                        throw new DataToBeSentToTheParapheurAreEmptyProblem($missingData);
                    }

                    $document['documentId'] = intval($document['documentId'] ?? 0);

                    $resourceToSign = [
                        'resId' => $document['resId']
                    ];

                    if (isset($document['isAttachment']) && $document['isAttachment']) {
                        $resourceToSign['resIdMaster'] = $resId;
                    }

                    $applySuccess = $this->signatureService
                        ->setConfig($signatureBook)
                        ->applySignature(
                            $document['documentId'],
                            $document['hashSignature'] ?? null,
                            $document['signatures'] ?? [],
                            $document['certificate'] ?? null,
                            $document['signatureContentLength'] ?? null,
                            $document['signatureFieldName'] ?? null,
                            $document['tmpUniqueId'] ?? null,
                            $accessToken,
                            $document['cookieSession'] ?? null,
                            $resourceToSign
                        );
                }

                if (is_array($applySuccess)) {
                    $error = $applySuccess['errors'];
                    if (!empty($applySuccess['context'])) {
                        $error .= " (Message = " . $applySuccess['context']['message'] . ")";
                    }
                    throw new SignatureNotAppliedProblem($error);
                }
            }
        } else {
            throw new NoDocumentsInSignatureBookForThisId();
        }

        return true;
    }
}
