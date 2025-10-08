<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User factory interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User\Port;

interface UserFactoryInterface
{
    public function createUserFromArray(array $values): UserInterface;
}
