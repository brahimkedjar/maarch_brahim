<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource file info class
 * @author dev@maarch.org
 */

namespace Resource\Domain;

class ResourceFileInfo
{
    private ?int $creatorId;
    private ?int $pageCount;
    private array $pathInfo;
    private string $fileContent;
    private string $formatFilename;
    private string $originalFormat;

    public function __construct(
        ?int $creatorId,
        ?int $pageCount,
        array $pathInfo,
        string $fileContent,
        string $formatFilename,
        string $originalFormat
    ) {
        $this->creatorId = $creatorId;
        $this->pageCount = $pageCount;
        $this->pathInfo = $pathInfo;
        $this->fileContent = $fileContent;
        $this->formatFilename = $formatFilename;
        $this->originalFormat = $originalFormat;
    }

    public function getCreatorId(): ?int
    {
        return $this->creatorId;
    }

    public function setCreatorId(?int $creatorId): void
    {
        $this->creatorId = $creatorId;
    }

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function setPageCount(?int $pageCount): void
    {
        $this->pageCount = $pageCount;
    }

    public function getPathInfo(): array
    {
        return $this->pathInfo;
    }

    public function setPathInfo(array $pathInfo): void
    {
        $this->pathInfo = $pathInfo;
    }

    public function getFileContent(): string
    {
        return $this->fileContent;
    }

    public function setFileContent(string $fileContent): void
    {
        $this->fileContent = $fileContent;
    }

    public function getFormatFilename(): string
    {
        return $this->formatFilename;
    }

    public function setFormatFilename(string $formatFilename): void
    {
        $this->formatFilename = $formatFilename;
    }

    public function getOriginalFormat(): string
    {
        return $this->originalFormat;
    }

    public function setOriginalFormat(string $originalFormat): void
    {
        $this->originalFormat = $originalFormat;
    }
}
