<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maarch Parapheur Group Service Mock
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\Group;

use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookGroupServiceInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

class MaarchParapheurGroupServiceMock implements SignatureBookGroupServiceInterface
{
    public bool $groupCreateCalled = false;
    public array|int $groupCreated = 5;
    public bool $groupUpdateCalled = false;
    public array|bool $groupUpdated = false;
    public array $privilege;
    public bool $groupUpdatePrivilegeCalled = false;
    public bool $privilegeIsChecked = false;
    public bool $groupIsDeletedCalled = false;
    public bool|array $groupIsDeleted = false;
    public bool|array $privilegesGroupUpdated = false;
    public bool $isPrivilegeRetrieveFailed = false;
    public bool $checked = false;

    /**
     * @param GroupInterface $group
     * @return array|int
     */
    public function createGroup(GroupInterface $group): array|int
    {
        $this->groupCreateCalled = true;
        $group->setLabel('test2');
        return $this->groupCreated;
    }

    /**
     * @param GroupInterface $group
     * @return array|bool
     */
    public function updateGroup(GroupInterface $group): array|bool
    {
        $this->groupUpdateCalled = true;
        $group->setLabel('test2');
        return $this->groupUpdated;
    }

    /**
     * @param GroupInterface $group
     * @return array|bool
     */
    public function deleteGroup(GroupInterface $group): array|bool
    {
        $this->groupIsDeletedCalled = true;
        return $this->groupIsDeleted;
    }

    /**
     * @param SignatureBookServiceConfig $config
     * @return SignatureBookGroupServiceInterface
     */
    public function setConfig(SignatureBookServiceConfig $config): SignatureBookGroupServiceInterface
    {
        return $this;
    }

    public function getGroupPrivileges(GroupInterface $group): array
    {
        if ($this->isPrivilegeRetrieveFailed) {
            $this->privilege = ['errors' => 'Error occurred while retrieving group information.'];
        }
        return $this->privilege;
    }

    /**
     * @param GroupInterface $group
     * @param string $privilege
     * @param bool $checked
     * @return array|bool
     */
    public function updatePrivilege(GroupInterface $group, string $privilege, bool $checked): array|bool
    {
        $this->groupUpdatePrivilegeCalled = true;
        return $this->privilegesGroupUpdated;
    }
}
