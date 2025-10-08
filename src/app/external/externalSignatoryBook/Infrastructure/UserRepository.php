<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User repository infrastructure
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\Infrastructure;

use ExternalSignatoryBook\Domain\Ports\UserRepositoryInterface;
use ExternalSignatoryBook\Domain\User;
use User\models\UserModel;

class UserRepository implements UserRepositoryInterface
{
    public function getRootUser(): User
    {
        $userId = UserModel::get([
            'select' => ['id'],
            'where'  => ['mode = ? OR mode = ?'],
            'data'   => ['root_visible', 'root_invisible'],
            'limit'  => 1
        ])[0]['id'];

        return new User($userId);
    }
}
