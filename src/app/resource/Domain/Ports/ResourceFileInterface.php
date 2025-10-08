<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource Data Interface
 * @author dev@maarch.org
 */

namespace Resource\Domain\Ports;

interface ResourceFileInterface
{
    /**
     * Build file path from docserver and document paths
     * @param string $docserverPath
     * @param string $documentPath
     * @param string $documentFilename
     * @return  string  Return the build file path or empty if docserverPath does not exist or empty
     */
    public function buildFilePath(string $docserverPath, string $documentPath, string $documentFilename): string;

    /**
     * @param string $folderPath
     * @return bool
     */
    public function folderExists(string $folderPath): bool;

    /**
     * @param string $filePath
     * @return bool
     */
    public function fileExists(string $filePath): bool;

    /**
     * @param string $docserverTypeId
     * @param string $filePath
     * @return string
     */
    public function getFingerPrint(string $docserverTypeId, string $filePath): string;

    /**
     * Retrieves file content.
     * @param string $filePath The path to the file.
     * @param bool $isEncrypted Flag if the file is encrypted. The default value is false
     * @return string|null Returns the content of the file as a string if successful,
     * or a string with value null on failure.
     */
    public function getFileContent(string $filePath, bool $isEncrypted = false): ?string;

    /**
     * Retrieves file content with watermark.
     * @param int $resId Resource id.
     * @param string|null $fileContent Resource file content.
     * @return string|null Returns the content of the file as a string if successful,
     * or a string with value 'null' on failure.
     */
    public function getWatermark(int $resId, ?string $fileContent): ?string;

    /**
     * @param int $resId
     * @param int $version
     * @param string $fileContent
     * @param string $extension
     * @return array
     */
    public function convertToThumbnail(int $resId, int $version, string $fileContent, string $extension): array;

    /**
     * Convert resource page to thumbnail.
     * @param int $resId Resource id.
     * @param string $type Resource type, 'resource' or 'attachment'.
     * @param int $page Resource page number.
     * @return string If returned contains 'errors:' then the conversion failed
     */
    public function convertOnePageToThumbnail(int $resId, string $type, int $page): string;

    /**
     * Retrieves the number of pages in a pdf file
     * @param string $filePath Resource path.
     * @return int Number of pages.
     */
    public function getTheNumberOfPagesInThePdfFile(string $filePath): int;
}
