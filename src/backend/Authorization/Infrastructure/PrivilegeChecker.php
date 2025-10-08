<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Privilege Checker
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Authorization\Infrastructure;

use Exception;
use Group\controllers\PrivilegeController;
use Group\models\PrivilegeModel;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

class PrivilegeChecker implements PrivilegeCheckerInterface
{
    /**
     * @param UserInterface $user
     * @param PrivilegeInterface $privilege
     * @return bool
     * @throws Exception
     */
    public function hasPrivilege(UserInterface $user, PrivilegeInterface $privilege): bool
    {
        return PrivilegeController::hasPrivilege([
            'privilegeId' => $privilege->getName(),
            'userId'      => $user->getId()
        ]);
    }


    /**
     * @param GroupInterface $group
     * @param PrivilegeInterface $privilege
     * @return bool
     * @throws Exception
     */
    public function hasGroupPrivilege(GroupInterface $group, PrivilegeInterface $privilege): bool
    {
        return PrivilegeModel::groupHasPrivilege([
            'privilegeId' => $privilege->getName(),
            'groupId'     => $group->getGroupId()
        ]);
    }
}
