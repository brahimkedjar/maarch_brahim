<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Service Json Config Loader class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure;

use Exception;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;
use MaarchCourrier\SignatureBook\Domain\UserWebService;
use SrcCore\models\CoreConfigModel;

class SignatureServiceJsonConfigLoader implements SignatureServiceConfigLoaderInterface
{
    /**
     * @return SignatureBookServiceConfig|null
     * @throws Exception Returns the signatureBook config
     */
    public function getSignatureServiceConfig(): ?SignatureBookServiceConfig
    {
        $config = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
        if (empty($config)) {
            return null;
        }

        $signatureServiceConfig = null;
        $config = $config['signatureBook'];

        if ($config) {
            $signatureServiceConfig = (new SignatureBookServiceConfig())
                ->setUrl($config['url'] ?? '')
                ->setUserWebService(
                    (new UserWebService())
                        ->setLogin($config['userIdParapheurWS'] ?? '')
                        ->setPassword($config['passwordParapheurWS'] ?? '')
                );
        }

        return $signatureServiceConfig ?? null;
    }
}
