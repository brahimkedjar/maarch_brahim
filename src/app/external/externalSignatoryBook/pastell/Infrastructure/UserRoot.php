<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Pastell UserRoot (for History)
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\pastell\Infrastructure;

use ExternalSignatoryBook\pastell\Domain\UserRootInterface;
use User\models\UserModel;

class UserRoot implements UserRootInterface
{
    /**
     * @return int
     */
    public function getUserRootId(): int
    {
        return UserModel::get([
            'select' => ['id'],
            'where'  => ['mode = ? OR mode = ?'],
            'data'   => ['root_visible', 'root_invisible'],
            'limit'  => 1
        ])[0]['id'];
    }
}
