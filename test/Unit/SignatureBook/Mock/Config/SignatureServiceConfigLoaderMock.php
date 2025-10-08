<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Service Config Loader Mock
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\Config;

use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;
use MaarchCourrier\SignatureBook\Domain\UserWebService;

class SignatureServiceConfigLoaderMock implements SignatureServiceConfigLoaderInterface
{
    public bool $isFileLoaded = true;
    public string $url = "https://example.com";

    public function getSignatureServiceConfig(): ?SignatureBookServiceConfig
    {
        if (!$this->isFileLoaded) {
            return  null;
        }

        return (new SignatureBookServiceConfig())
            ->setUrl($this->url)
            ->setUserWebService(
                (new UserWebService())
                    ->setLogin('ccornillac@maarch.com')
                    ->setPassword('maarch')
            );
    }
}
