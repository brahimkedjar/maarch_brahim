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

namespace ExternalSignatoryBook\pastell\Infrastructure;

use Convert\controllers\ConvertPdfController;
use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Exception;
use ExternalSignatoryBook\pastell\Domain\ResourceFileInterface;
use Resource\controllers\StoreController;

class ResourceFile implements ResourceFileInterface
{
    /**
     * Getting the file path of main file
     * @param int $resId
     * @return string
     * @throws Exception
     */
    public function getMainResourceFilePath(int $resId): string
    {
        $adrMainInfo = ConvertPdfController::getConvertedPdfById(['resId' => $resId, 'collId' => 'letterbox_coll']);
        // Checking extension of file
        if (empty($adrMainInfo['docserver_id']) || strtolower(pathinfo($adrMainInfo['filename'], PATHINFO_EXTENSION)) != 'pdf') {
            return 'Error: Document ' . $resId . ' is not converted in pdf';
        } else {
            $letterboxPath = DocserverModel::getByDocserverId(['docserverId' => $adrMainInfo['docserver_id'], 'select' => ['path_template']]);
            return $letterboxPath['path_template'] . str_replace('#', '/', $adrMainInfo['path']) . $adrMainInfo['filename'];
        }
    }

    /**
     * @param int $resId
     * @param string $fingerprint
     * @return string|null
     * @throws Exception
     */
    public function getAttachmentFilePath(int $resId, string $fingerprint): string
    {
        $adrInfo = AdrModel::getConvertedDocumentById(['resId' => $resId, 'collId' => 'attachments_coll', 'type' => 'PDF']);
        if (empty($adrInfo['docserver_id']) || strtolower(pathinfo($adrInfo['filename'], PATHINFO_EXTENSION)) != 'pdf') {
            return 'Error: Document ' . $resId . ' is not converted in pdf';
        }
        $annexeAttachmentPath = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        $filePath = $annexeAttachmentPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $adrInfo['path']) . $adrInfo['filename'];

        // Checking fingerprint
        $docserverType = DocserverTypeModel::getById(['id' => $annexeAttachmentPath['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $realFingerprint = StoreController::getFingerPrint(['filePath' => $filePath, 'mode' => $docserverType['fingerprint_mode']]);
        if ($adrInfo['fingerprint'] != $realFingerprint) {
            return 'Error: Fingerprints do not match';
        }

        return $filePath;
    }
}
