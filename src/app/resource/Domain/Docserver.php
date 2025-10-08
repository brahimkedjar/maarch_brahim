<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Docserver class
 * @author dev@maarch.org
 */

namespace Resource\Domain;

class Docserver
{
    private int $id;
    private string $docserverId;
    private string $docserverTypeId;
    private string $pathTemplate;
    private bool $isEncrypted;

    public function __construct(
        int $id,
        string $docserverId,
        string $docserverTypeId,
        string $pathTemplate,
        bool $isEncrypted
    ) {
        $this->id = $id;
        $this->docserverId = $docserverId;
        $this->docserverTypeId = $docserverTypeId;
        $this->pathTemplate = $pathTemplate;
        $this->isEncrypted = $isEncrypted;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getDocserverId(): string
    {
        return $this->docserverId;
    }

    public function setDocserverId(string $docserverId): void
    {
        $this->docserverId = $docserverId;
    }

    public function getDocserverTypeId(): string
    {
        return $this->docserverTypeId;
    }

    public function setDocserverTypeId(string $docserverTypeId): void
    {
        $this->docserverTypeId = $docserverTypeId;
    }

    public function getPathTemplate(): string
    {
        return $this->pathTemplate;
    }

    public function setPathTemplate(string $pathTemplate): void
    {
        $this->pathTemplate = $pathTemplate;
    }

    public function getIsEncrypted(): bool
    {
        return $this->isEncrypted;
    }

    public function setIsEncrypted(bool $isEncrypted): void
    {
        $this->isEncrypted = $isEncrypted;
    }
}
