<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MaarchParapheurSignatureServiceMock class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action;

use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

class MaarchParapheurSignatureServiceMock implements SignatureServiceInterface
{
    private SignatureBookServiceConfig $config;
    public array|bool $applySignature = true;

    public array $returnFromParapheur = [
        'encodedDocument' => 'Contenu du fichier',
        'mimetype'        => "application/pdf",
        'filename'        => "PDF_signature.pdf"
    ];

    public function setConfig(SignatureBookServiceConfig $config): SignatureServiceInterface
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param int $documentId
     * @param string|null $hashSignature
     * @param array|null $signatures
     * @param string|null $certificate
     * @param string|null $signatureContentLength
     * @param string|null $signatureFieldName
     * @param string|null $tmpUniqueId
     * @param string $accessToken
     * @param string|null $cookieSession
     * @param array $resourceToSign
     * @return array|bool
     */
    public function applySignature(
        int $documentId,
        ?string $hashSignature,
        ?array $signatures,
        ?string $certificate,
        ?string $signatureContentLength,
        ?string $signatureFieldName,
        ?string $tmpUniqueId,
        string $accessToken,
        ?string $cookieSession,
        array $resourceToSign
    ): array|bool {
        return $this->applySignature;
    }

    /**
     * @param string $accessToken
     * @param string $urlRetrieveDoc
     * @return array[]
     */
    public function retrieveDocumentSign(string $accessToken, string $urlRetrieveDoc): array
    {
        return ['response' => $this->returnFromParapheur];
    }
}
