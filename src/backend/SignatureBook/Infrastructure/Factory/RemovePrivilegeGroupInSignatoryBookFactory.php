<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Remove Privilege Group In Signatory Group Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use MaarchCourrier\Authorization\Infrastructure\PrivilegeChecker;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurGroupService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;
use MaarchCourrier\SignatureBook\Application\Group\RemovePrivilegeGroupInSignatoryBook;

class RemovePrivilegeGroupInSignatoryBookFactory
{
    /**
     * @return RemovePrivilegeGroupInSignatoryBook
     */
    public function create(): RemovePrivilegeGroupInSignatoryBook
    {
        $signatureBookGroup = new MaarchParapheurGroupService();
        $signatureBookConfigLoader = new SignatureServiceJsonConfigLoader();
        $privilegeChecker = new PrivilegeChecker();

        return new RemovePrivilegeGroupInSignatoryBook(
            $signatureBookGroup,
            $signatureBookConfigLoader,
            $privilegeChecker
        );
    }
}
