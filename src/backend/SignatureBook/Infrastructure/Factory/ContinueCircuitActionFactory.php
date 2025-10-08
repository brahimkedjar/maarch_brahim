<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ContinueCircuitActionFactory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use Exception;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\SignatureBook\Application\Action\ContinueCircuitAction;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurSignatureService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;

class ContinueCircuitActionFactory
{
    /**
     * @throws Exception
     */
    public static function create(): ContinueCircuitAction
    {
        $currentUser = new CurrentUserInformations();
        $signatureService = new MaarchParapheurSignatureService();
        $IsNewInternalParapheurEnabled = new Environment();
        $IsNewInternalParapheurEnabled = $IsNewInternalParapheurEnabled->isNewInternalParapheurEnabled();
        $SignatureServiceConfigLoader = new SignatureServiceJsonConfigLoader();

        return new ContinueCircuitAction(
            $currentUser,
            $signatureService,
            $SignatureServiceConfigLoader,
            $IsNewInternalParapheurEnabled
        );
    }
}
