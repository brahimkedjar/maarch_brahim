<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief RetrieveSignedResource class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Webhook;

use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;

use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\NoEncodedContentRetrievedProblem;
use MaarchCourrier\SignatureBook\Domain\SignedResource;

class RetrieveSignedResource
{
    /**
     * @param CurrentUserInterface $currentUser
     * @param SignatureServiceInterface $maarchParapheurSignatureService
     */
    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly SignatureServiceInterface $maarchParapheurSignatureService,
    ) {
    }

    /**
     * @param SignedResource $signedResource
     * @param string $urlRetrieveDoc
     * @return SignedResource
     * @throws NoEncodedContentRetrievedProblem
     */
    public function retrieveSignedResourceContent(
        SignedResource $signedResource,
        string $urlRetrieveDoc
    ): SignedResource {
        $accessToken = $this->currentUser->generateNewToken();

        $curlResponseContent = $this->maarchParapheurSignatureService->retrieveDocumentSign(
            $accessToken,
            $urlRetrieveDoc
        );

        if (!empty($curlResponseContent['response']['encodedDocument'])) {
            $signedResource->setEncodedContent($curlResponseContent['response']['encodedDocument']);
        } else {
            throw new NoEncodedContentRetrievedProblem();
        }

        $signedResource->setUserSerialId($this->currentUser->getCurrentUserId());

        return $signedResource;
    }
}
