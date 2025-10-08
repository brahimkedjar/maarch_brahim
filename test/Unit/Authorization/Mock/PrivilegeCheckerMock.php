<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Privilege Checker Mock
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Tests\Unit\Authorization\Mock;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

class PrivilegeCheckerMock implements PrivilegeCheckerInterface
{
    public bool $hasPrivilege = false;
    public bool $hasGroupPrivilege = false;
    public bool $hasGroupPrivilegeCalled = false;

    /**
     * @param UserInterface $user
     * @param PrivilegeInterface $privilege
     * @return bool
     */
    public function hasPrivilege(UserInterface $user, PrivilegeInterface $privilege): bool
    {
        return $this->hasPrivilege;
    }

    /**
     * @param GroupInterface $group
     * @param PrivilegeInterface $privilege
     * @return bool
     */
    public function hasGroupPrivilege(GroupInterface $group, PrivilegeInterface $privilege): bool
    {
        $this->hasGroupPrivilegeCalled = true;
        return $this->hasGroupPrivilege;
    }
}
