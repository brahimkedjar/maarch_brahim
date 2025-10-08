<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureServiceConfigLoaderRepositoryMock class
 * @author dev@maarch.org
 */
namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action;

use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;


class SignatureServiceJsonConfigLoaderMock implements SignatureServiceConfigLoaderInterface
{
    public ?SignatureBookServiceConfig $signatureServiceConfigLoader = null;

    public function __construct()
    {
        $this->signatureServiceConfigLoader = new SignatureBookServiceConfig(
            'test/url/maarch/parapheur/api'
        );
    }
    public function getSignatureServiceConfig(): ?SignatureBookServiceConfig
    {
        return $this->signatureServiceConfigLoader;
    }
}
