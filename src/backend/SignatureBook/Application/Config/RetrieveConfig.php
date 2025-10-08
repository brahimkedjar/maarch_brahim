<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Config class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Config;

use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookConfigReturnApi;

class RetrieveConfig
{
    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly SignatureServiceConfigLoaderInterface $loader
    ) {
    }

    /**
     * @return SignatureBookConfigReturnApi
     */
    public function getConfig(): SignatureBookConfigReturnApi
    {
        $config = (new SignatureBookConfigReturnApi())
            ->setIsNewInternalParaph($this->environment->isNewInternalParapheurEnabled())
            ->setUrl($this->loader->getSignatureServiceConfig()->getUrl());

        if (!$this->environment->isNewInternalParapheurEnabled()) {
            $config->setUrl('');
        }

        return $config;
    }
}
