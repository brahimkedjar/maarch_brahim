<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Repository Interface
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\Domain\Ports;

use ExternalSignatoryBook\Domain\User;

interface UserRepositoryInterface
{
    /**
     * @return User
     */
    public function getRootUser(): User;
}
