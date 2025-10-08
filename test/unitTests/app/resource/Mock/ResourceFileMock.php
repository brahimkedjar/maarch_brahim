<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\resource\Mock;

use Resource\Domain\Ports\ResourceFileInterface;

class ResourceFileMock implements ResourceFileInterface
{
    public bool $doesFolderExist = true;
    public bool $doesFileExist = true;
    public bool $doesDocserverPathExist = true;
    public bool $doesResourceFileGetContentFail = false;
    public bool $doesWatermarkInResourceFileContentFail = false;
    public bool $doesResourceConvertToThumbnailFailed = false;
    public bool $returnResourceThumbnailFileContent = false;
    public bool $doesResourceConvertOnePageToThumbnailFailed = false;
    public string $mainResourceFileContent = 'original file content';
    public string $mainWatermarkInResourceFileContent = 'watermark in file content';
    public string $docserverPath = 'install/samples/resources/';
    public string $documentFingerprint = 'file fingerprint';
    public string $resourceThumbnailFileContent = 'resource thumbnail of an img';
    public string $noThumbnailFileContent = 'thumbnail of no img';

    /**
     * @param string $docserverPath
     * @param string $documentPath
     * @param string $documentFilename
     * @return string
     */
    public function buildFilePath(string $docserverPath, string $documentPath, string $documentFilename): string
    {
        if (empty($this->docserverPath) || !$this->doesDocserverPathExist) {
            return '';
        }

        return $this->docserverPath . str_replace('#', DIRECTORY_SEPARATOR, $documentPath) . $documentFilename;
    }

    /**
     * @param string $folderPath
     * @return bool
     */
    public function folderExists(string $folderPath): bool
    {
        if (empty($folderPath)) {
            return false;
        }
        return $this->doesFolderExist;
    }

    /**
     * @param string $filePath
     * @return bool
     */
    public function fileExists(string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }
        return $this->doesFileExist;
    }

    /**
     * @param string $docserverTypeId
     * @param string $filePath
     * @return string
     */
    public function getFingerPrint(string $docserverTypeId, string $filePath): string
    {
        if (empty($docserverTypeId)) {
            return '';
        }
        if (empty($filePath)) {
            return '';
        }

        return $this->documentFingerprint;
    }

    /**
     * @param string $filePath
     * @param bool $isEncrypted
     * @return string|null
     */
    public function getFileContent(string $filePath, bool $isEncrypted = false): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        if ($this->doesResourceFileGetContentFail) {
            if ($this->returnResourceThumbnailFileContent && str_contains($filePath, 'noThumbnail.png')) {
                return $this->noThumbnailFileContent;
            }
            return null;
        }

        if ($this->returnResourceThumbnailFileContent && str_contains($filePath, 'noThumbnail.png')) {
            return $this->noThumbnailFileContent;
        }

        return $this->returnResourceThumbnailFileContent ? $this->resourceThumbnailFileContent : $this->mainResourceFileContent;
    }

    /**
     * @param int $resId
     * @param string|null $fileContent
     * @return string|null
     */
    public function getWatermark(int $resId, ?string $fileContent): ?string
    {
        if ($resId <= 0) {
            return null;
        }

        if (!$this->doesWatermarkInResourceFileContentFail) {
            return $this->mainWatermarkInResourceFileContent;
        } else {
            return null;
        }
    }

    /**
     * @param int $resId
     * @param int $version
     * @param string $fileContent
     * @param string $extension
     * @return array|string[]
     */
    public function convertToThumbnail(int $resId, int $version, string $fileContent, string $extension): array
    {
        if ($this->doesResourceConvertToThumbnailFailed) {
            return ['errors' => 'Conversion to thumbnail failed'];
        }
        return [];
    }

    /**
     * @param int $resId
     * @param string $type
     * @param int $page
     * @return string
     */
    public function convertOnePageToThumbnail(int $resId, string $type, int $page): string
    {
        if ($this->doesResourceConvertOnePageToThumbnailFailed) {
            return 'errors:';
        }
        return 'true';
    }

    /**
     * @param string $filePath
     * @return int
     */
    public function getTheNumberOfPagesInThePdfFile(string $filePath): int
    {
        /*
        if (empty($filePath)) {
            throw new Exception("Throw an exception when get pdf file");
        }

        if ($this->triggerAnExceptionWhenGetTheNumberOfPagesInThePdfFile) {
            throw new Exception("Throw an exception when parsing pdf file");
        }
        */
        return 1;
    }
}
