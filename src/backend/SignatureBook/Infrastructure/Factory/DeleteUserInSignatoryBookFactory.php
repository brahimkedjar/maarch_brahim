<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Delete User In Signatory Book Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use MaarchCourrier\SignatureBook\Application\User\DeleteUserInSignatoryBook;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurUserService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;

class DeleteUserInSignatoryBookFactory
{
    /**
     * @return DeleteUserInSignatoryBook
     */
    public static function create(): DeleteUserInSignatoryBook
    {
        $signatureBookUser = new MaarchParapheurUserService();
        $SignatureServiceConfigLoader = new SignatureServiceJsonConfigLoader();

        return new DeleteUserInSignatoryBook(
            $signatureBookUser,
            $SignatureServiceConfigLoader,
        );
    }
}
