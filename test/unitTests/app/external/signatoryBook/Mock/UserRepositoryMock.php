<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\external\signatoryBook\Mock;

use ExternalSignatoryBook\Domain\Ports\UserRepositoryInterface;
use ExternalSignatoryBook\Domain\User;

class UserRepositoryMock implements UserRepositoryInterface
{
    public function getRootUser(): User
    {
        return new User(1);
    }
}
