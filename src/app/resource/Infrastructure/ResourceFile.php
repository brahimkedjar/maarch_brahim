<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource file
 * @author dev@maarch.org
 */

namespace Resource\Infrastructure;

use Convert\controllers\ConvertThumbnailController;
use Docserver\models\DocserverTypeModel;
use Exception;
use Resource\Domain\Ports\ResourceFileInterface;
use Resource\controllers\StoreController;
use Resource\controllers\WatermarkController;
use SetaPDF_Core_Document;
use setasign\Fpdi\Fpdi;
use SrcCore\controllers\PasswordController;
use SrcCore\models\CoreConfigModel;
use Throwable;

class ResourceFile implements ResourceFileInterface
{
    /**
     * @param string $docserverPath
     * @param string $documentPath
     * @param string $documentFilename
     * @return string
     */
    public function buildFilePath(string $docserverPath, string $documentPath, string $documentFilename): string
    {
        if (empty($docserverPath) || !file_exists($docserverPath)) {
            return '';
        }

        return $docserverPath . str_replace('#', DIRECTORY_SEPARATOR, $documentPath) . $documentFilename;
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
        return is_dir($folderPath);
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
        return file_exists($filePath);
    }

    /**
     * @param string $docserverTypeId
     * @param string $filePath
     * @return string
     * @throws Exception
     */
    public function getFingerPrint(string $docserverTypeId, string $filePath): string
    {
        if (empty($docserverTypeId)) {
            return '';
        }
        if (empty($filePath)) {
            return '';
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserverTypeId, 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint([
            'filePath' => $filePath,
            'mode'     => $docserverType['fingerprint_mode']
        ]);
        return $fingerprint;
    }

    /**
     * @param string $filePath
     * @param bool $isEncrypted
     * @return string|null
     * @throws Exception
     */
    public function getFileContent(string $filePath, bool $isEncrypted = false): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            return null;
        }

        if (CoreConfigModel::isEnableDocserverEncryption() && $isEncrypted) {
            $fileContent = PasswordController::decrypt(['encryptedData' => base64_encode($fileContent)]);
        }

        return $fileContent;
    }

    /**
     * @param int $resId
     * @param string|null $fileContent
     * @return string|null
     * @throws Exception
     */
    public function getWatermark(int $resId, ?string $fileContent): ?string
    {
        if ($resId <= 0) {
            return null;
        }
        if ($fileContent === null) {
            return null;
        }
        return WatermarkController::watermarkResource(['resId' => $resId, 'fileContent' => $fileContent]);
    }

    /**
     * @param int $resId
     * @param int $version
     * @param string $fileContent
     * @param string $extension
     * @return true[]
     * @throws Exception
     */
    public function convertToThumbnail(int $resId, int $version, string $fileContent, string $extension): array
    {
        $check = ConvertThumbnailController::convert([
            'type'        => 'resource',
            'resId'       => $resId,
            'fileContent' => $fileContent,
            'extension'   => $extension,
            'version'     => $version
        ]);
        if ($check['errors']) {
            return $check;
        }
        return ['success' => true];
    }

    /**
     * @param int $resId
     * @param string $type
     * @param int $page
     * @return string
     */
    public function convertOnePageToThumbnail(int $resId, string $type, int $page): string
    {
        try {
            $check = ConvertThumbnailController::convertOnePage(['type' => $type, 'resId' => $resId, 'page' => $page]);
        } catch (Throwable $th) {
            return "errors: " . $th->getMessage();
        }

        if (isset($check['errors'])) {
            return "errors: " . $check['errors'];
        }

        return $check;
    }

    /**
     * Retrieves the number of pages in a pdf file
     *
     * @param string $filePath Resource path.
     *
     * @return  int     Number of pages.
     */
    public function getTheNumberOfPagesInThePdfFile(string $filePath): int
    {
        $pageCount = 0;

        try {
            $libPath = CoreConfigModel::getSetaSignFormFillerLibrary();

            if (!empty($libPath)) {
                require_once($libPath);

                $document = SetaPDF_Core_Document::loadByFilename($filePath);
                $pages = $document->getCatalog()->getPages();
                $pageCount = count($pages);
            } else {
                $libPath = CoreConfigModel::getFpdiPdfParserLibrary();
                if (file_exists($libPath)) {
                    require_once($libPath);
                }
                $pdf = new Fpdi('P', 'pt');
                $pageCount = $pdf->setSourceFile($filePath);
            }
        } catch (Exception $e) {
            $pageCount = 0;
        }
        return $pageCount;
    }
}
