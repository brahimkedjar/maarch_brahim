<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Book User Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

interface SignatureBookGroupServiceInterface
{
    /**
     * @param GroupInterface $group
     * @return array|int
     */
    public function createGroup(GroupInterface $group): array|int;

    /**
     * @param GroupInterface $group
     * @return array|bool
     */
    public function updateGroup(GroupInterface $group): array|bool;

    /**
     * @param GroupInterface $group
     * @return array|bool
     */
    public function deleteGroup(GroupInterface $group): array|bool;

    /**
     * @param SignatureBookServiceConfig $config
     * @return SignatureBookGroupServiceInterface
     */
    public function setConfig(SignatureBookServiceConfig $config): SignatureBookGroupServiceInterface;

    public function getGroupPrivileges(GroupInterface $group): array;

    /**
     * @param GroupInterface $group
     * @param string $privilege
     * @param bool $checked
     * @return array|bool
     */
    public function updatePrivilege(GroupInterface $group, string $privilege, bool $checked): array|bool;
}
