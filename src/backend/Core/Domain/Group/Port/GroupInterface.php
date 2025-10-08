<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Group\Port;

interface GroupInterface
{
    /**
     * @return string
     */
    public function getGroupId(): string;

    /**
     * @param string $groupId
     * @return GroupInterface
     */
    public function setGroupId(string $groupId): GroupInterface;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @param string $label
     * @return GroupInterface
     */
    public function setLabel(string $label): GroupInterface;

    /**
     * @return array|null
     */
    public function getExternalId(): ?array;

    /**
     * @param array|null $externalId
     * @return GroupInterface
     */
    public function setExternalId(?array $externalId): GroupInterface;
}
