<?php

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

interface SignatureBookProofServiceInterface
{
    public function setConfig(SignatureBookServiceConfig $config): SignatureBookProofServiceInterface;

    public function retrieveProofFile(int $documentId, string $accessToken): array;
}
