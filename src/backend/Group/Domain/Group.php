<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Group\Domain;

use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;

class Group implements GroupInterface
{
    private string $groupId;
    private ?array $externalId;
    private string $label;

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return GroupInterface
     */
    public function setLabel(string $label): GroupInterface
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getExternalId(): ?array
    {
        return $this->externalId;
    }

    /**
     * @param array|null $externalId
     * @return GroupInterface
     */
    public function setExternalId(?array $externalId): GroupInterface
    {
        $this->externalId = $externalId;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroupId(): string
    {
        return $this->groupId;
    }

    /**
     * @param string $groupId
     * @return GroupInterface
     */
    public function setGroupId(string $groupId): GroupInterface
    {
        $this->groupId = $groupId;
        return $this;
    }
}
