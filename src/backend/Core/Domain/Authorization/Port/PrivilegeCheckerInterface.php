<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Privilege Checker Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Authorization\Port;

use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface PrivilegeCheckerInterface
{
    /**
     * @param UserInterface $user
     * @param PrivilegeInterface $privilege
     * @return bool
     */
    public function hasPrivilege(UserInterface $user, PrivilegeInterface $privilege): bool;

    /**
     * @param GroupInterface $group
     * @param PrivilegeInterface $privilege
     * @return bool
     */
    public function hasGroupPrivilege(GroupInterface $group, PrivilegeInterface $privilege): bool;
}
