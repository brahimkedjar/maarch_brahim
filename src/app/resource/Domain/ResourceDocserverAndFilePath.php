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

class ResourceDocserverAndFilePath
{
    private Docserver $docserver;
    private string $filePath;

    /**
     * @param Docserver $docserver
     * @param string $filePath
     */
    public function __construct(Docserver $docserver, string $filePath)
    {
        $this->docserver = $docserver;
        $this->filePath = $filePath;
    }

    public function getDocserver(): Docserver
    {
        return $this->docserver;
    }

    public function setDocserver(Docserver $docserver): void
    {
        $this->docserver = $docserver;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }
}
