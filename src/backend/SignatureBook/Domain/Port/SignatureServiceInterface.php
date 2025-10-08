<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureServiceInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

interface SignatureServiceInterface
{
    public function setConfig(SignatureBookServiceConfig $config): SignatureServiceInterface;

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
    ): array|bool;

    public function retrieveDocumentSign(string $accessToken, string $urlRetrieveDoc): array;
}
