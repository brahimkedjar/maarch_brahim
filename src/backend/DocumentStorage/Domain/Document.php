<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Document Class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\DocumentStorage\Domain;

class Document
{
    private string $fileName;
    private string $fileExtension;

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     *
     * @return Document
     */
    public function setFileName(string $fileName): Document
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * @param string $fileExtension
     *
     * @return Document
     */
    public function setFileExtension(string $fileExtension): Document
    {
        $this->fileExtension = $fileExtension;
        return $this;
    }
}
