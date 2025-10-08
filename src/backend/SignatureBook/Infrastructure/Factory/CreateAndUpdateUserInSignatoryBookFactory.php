<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Create And Update User In Signatory Book Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use MaarchCourrier\SignatureBook\Application\User\CreateAndUpdateUserInSignatoryBook;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurUserService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;

class CreateAndUpdateUserInSignatoryBookFactory
{
    /**
     * @return CreateAndUpdateUserInSignatoryBook
     */
    public function create(): CreateAndUpdateUserInSignatoryBook
    {
        $signatureBookUser = new MaarchParapheurUserService();
        $SignatureServiceConfigLoader = new SignatureServiceJsonConfigLoader();

        return new CreateAndUpdateUserInSignatoryBook(
            $signatureBookUser,
            $SignatureServiceConfigLoader
        );
    }
}
