<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Perimeter Checker Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource\Port;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface MainResourcePerimeterCheckerInterface
{
    /**
     * Check if user has rights over the resource
     *
     * @param   int $resId Resource id
     * @param   UserInterface $user User
     *
     * @return  bool
     */
    public function hasRightByResId(int $resId, UserInterface $user): bool;
}
