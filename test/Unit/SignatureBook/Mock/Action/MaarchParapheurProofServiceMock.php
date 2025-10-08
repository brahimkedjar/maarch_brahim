<?php

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action;

use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookProofServiceInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

class MaarchParapheurProofServiceMock implements SignatureBookProofServiceInterface
{
    private SignatureBookServiceConfig $config;

    public array $returnFromParapheur = [
        'encodedProofDocument' => 'Contenu du fichier de preuve',
        'format'        => "zip"
    ];

    public function setConfig(SignatureBookServiceConfig $config): SignatureBookProofServiceInterface
    {
        $this->config = $config;
        return $this;
    }

    public function retrieveProofFile(int $documentId, string $accessToken): array
    {
        return $this->returnFromParapheur;
    }
}