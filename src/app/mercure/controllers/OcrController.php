<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief OcrController Controller
 * @author dev@maarch.org
 */

namespace Mercure\controllers;

use Attachment\models\AttachmentModel;
use Configuration\models\ConfigurationModel;
use Convert\controllers\FullTextController;
use Convert\models\AdrModel;
use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use Exception;
use Resource\models\ResModel;
use SrcCore\controllers\LogsController;
use Respect\Validation\Validator;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;

class OcrController
{
    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws Exception
     */
    public function ocrRequest(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (!Validator::notEmpty()->validate($body['type'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body type is empty']);
        }
        if (!Validator::notEmpty()->validate($body['resId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body resId is empty']);
        }

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        if (empty($configuration)) {
            return $response->withStatus(400)->withJson(['errors' => 'Mercure configuration is not enabled']);
        }

        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration['enabledOcr'] ?? null) || !$configuration['enabledOcr']) {
            return $response->withStatus(200)->withJson(['message' => 'Mercure OCR is not enabled']);
        }

        $collId = '';
        if ($body['type'] == 'resource') {
            $collId = 'letterbox_coll';
            $infosDoc = ResModel::getById([
                'select' => ['format', 'docserver_id', 'path', 'filename'],
                'resId'  => $body['resId']
            ]);
        } elseif ($body['type'] == 'attachment') {
            $collId = 'attachments_coll';
            $infosDoc = AttachmentModel::getById([
                'select' => ['format', 'docserver_id', 'path', 'filename'],
                'id'     => $body['resId']
            ]);
        } else {
            return $response->withStatus(400)->withJson([
                'message' => "Type de document non valable (resource | attachment)"
            ]);
        }

        //Test OCR file exists in parameters
        if (!empty($body['filename'])) {
            $testOcrFile = CoreConfigModel::getTmpPath() . "OCRFile_" . $body['filename'];
            if (is_file($testOcrFile)) {
                $ocrConvert['convertedFile'] = $testOcrFile;
            }
        }

        if (strtolower($infosDoc['format']) == 'pdf') {
            if (!isset($ocrConvert['convertedFile'])) {
                if ($configuration['mwsOcrPriority']) {
                    $ocrConvert = MwsController::launchOcrMws([
                        'collId' => $collId,
                        'resId'  => $body['resId']
                    ]);
                } else {
                    $ocrConvert = OcrController::launchOcrTesseract([
                        'collId' => $collId,
                        'resId'  => $body['resId']
                    ]);
                }
            }
        } else {
            return $response->withStatus(200)->withJson(['message' => "Not PDF file, no OCR needed"]);
        }

        if (isset($ocrConvert['errors'])) {
            return $response->withStatus(500)->withJson([
                'errors' => $ocrConvert['errors'],
                'body'   => $ocrConvert['body'] ?? null,
                'output' => $ocrConvert['output'] ?? null
            ]);
        } elseif (isset($ocrConvert['message'])) {
            return $response->withStatus(200)->withJson(['message' => $ocrConvert['message']]);
        }

        $resource = file_get_contents($ocrConvert['convertedFile']);
        $storeResult = DocserverController::storeResourceOnDocServer([
            'collId'          => $collId,
            'docserverTypeId' => 'CONVERT',
            'encodedResource' => base64_encode($resource),
            'format'          => 'pdf'
        ]);

        if (!empty($storeResult['errors'])) {
            return $response->withStatus(400)->withJson(
                ['message' => "[OcrController] {$storeResult['errors']}"]
            );
        }

        $document = AdrModel::getConvertedDocumentById([
            'select' => ['docserver_id', 'path', 'filename', 'fingerprint'],
            'resId'  => $body['resId'],
            'collId' => $collId,
            'type'   => 'PDF'
        ]);

        if ($collId == 'letterbox_coll') {
            if (empty($document)) {
                AdrModel::createDocumentAdr([
                    'resId'       => $body['resId'],
                    'type'        => 'PDF',
                    'docserverId' => $storeResult['docserver_id'],
                    'path'        => $storeResult['destination_dir'],
                    'filename'    => $storeResult['file_destination_name'],
                    'version'     => $body['version'] ?? 1,
                    'fingerprint' => $storeResult['fingerPrint']
                ]);
            } else {
                AdrModel::updateDocumentAdr([
                    'set'   => [
                        'fingerprint' => $storeResult['fingerPrint'],
                        'filename'    => $storeResult['file_destination_name']
                    ],
                    'where' => ['res_id = ?', 'type = ?', 'version = ?'],
                    'data'  => [$body['resId'], 'PDF', $body['version'] ?? 1]
                ]);
            }
        } elseif (empty($document)) {
            AdrModel::createAttachAdr([
                'resId'       => $body['resId'],
                'type'        => 'PDF',
                'docserverId' => $storeResult['docserver_id'],
                'path'        => $storeResult['destination_dir'],
                'filename'    => $storeResult['file_destination_name'],
                'fingerprint' => $storeResult['fingerPrint']
            ]);
        } else {
            AdrModel::updateAttachmentAdr([
                'set'   => [
                    'fingerprint' => $storeResult['fingerPrint'],
                    'filename'    => $storeResult['file_destination_name']
                ],
                'where' => ['res_id = ?', 'type = ?'],
                'data'  => [$body['resId'], 'PDF']
            ]);
        }

        FullTextController::indexDocument(['resId' => $body['resId'], 'collId' => $collId]);

        return $response->withJson([
            'docserver_id' => $storeResult['docserver_id'],
            'path'         => $storeResult['destination_dir'],
            'filename'     => $storeResult['file_destination_name'],
            'fingerprint'  => $storeResult['fingerPrint']
        ]);
    }

    /**
     * @param array $aArgs
     *
     * @return array
     * @throws Exception
     */
    private static function launchOcrTesseract(
        array $aArgs
    ): array {
        ValidatorModel::notEmpty($aArgs, ['collId', 'resId']);
        ValidatorModel::stringType($aArgs, ['collId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        if ($aArgs['collId'] == 'letterbox_coll') {
            $tablename = 'res_letterbox';
            $resource = ResModel::getById([
                'resId'  => $aArgs['resId'],
                'select' => ['docserver_id', 'path', 'filename', 'format']
            ]);
        } else {
            $tablename = 'res_attachments';
            $resource = AttachmentModel::getById([
                'id'     => $aArgs['resId'],
                'select' => ['docserver_id', 'path', 'filename', 'format']
            ]);
        }

        if (empty($resource['docserver_id']) || empty($resource['path']) || empty($resource['filename'])) {
            return ['errors' => '[OcrController] Resource does not exist'];
        }

        $docserver = DocserverModel::getByDocserverId([
            'docserverId' => $resource['docserver_id'],
            'select'      => ['path_template']
        ]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => '[OcrController] Docserver does not exist'];
        }

        $pathToDocument = $docserver['path_template'] .
            str_replace('#', DIRECTORY_SEPARATOR, $resource['path']) . $resource['filename'];
        if (!is_file($pathToDocument)) {
            return ['errors' => '[OcrController] Converted document not found on docserver'];
        } elseif (!is_readable($pathToDocument)) {
            return ['errors' => '[OcrController] Converted document is not readable'];
        }

        $tmpFile = CoreConfigModel::getTmpPath() . basename($pathToDocument) . rand() . '.txt';
        $pdfToText = exec("pdftotext " . escapeshellarg($pathToDocument) . " " . escapeshellarg($tmpFile));
        if (!is_file($tmpFile)) {
            return ['errors' => '[OcrController] Convert to tiff : Command pdftotext did not work : ' . $pdfToText];
        }

        $fileContent = trim(file_get_contents($tmpFile));
        $fileContent = preg_replace('/[^A-Za-z0-9\-]/', '', $fileContent);
        if (strlen($fileContent) > 0) {
            return ['message' => 'OCR Task not needed'];
        } else {
            //Conversion en TIFF
            $tmpFile = CoreConfigModel::getTmpPath() . basename($pathToDocument) . rand() . '.tiff';
            $cmdConvertTiff = "convert -density 300 " . escapeshellarg($pathToDocument) .
                " -depth 8 -strip -background white -alpha off " . escapeshellarg($tmpFile);

            $outputConvert = null;
            $retvalConvert = null;
            exec($cmdConvertTiff . ' 2>&1', $outputConvert, $retvalConvert);

            if (!is_file($tmpFile)) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'mercure',
                    'level'     => 'ERROR',
                    'tableName' => $tablename,
                    'recordId'  => $aArgs['resId'],
                    'eventType' => "OCR Tesseract - Convert to tiff : Error during conversion",
                    'eventId'   => "command : { $cmdConvertTiff }"
                ]);

                return ['errors' => '[OcrController] Convert to tiff : Error during conversion'];
            } else {
                //Conversion OCR
                $tmpFileOcr = CoreConfigModel::getTmpPath() . basename($pathToDocument, ".pdf") . rand();
                $cmdConvertOcr = "tesseract " . escapeshellarg($tmpFile) . " " .
                    escapeshellarg($tmpFileOcr) . " -l fra pdf";

                $outputConvert = null;
                $retvalConvert = null;
                exec($cmdConvertOcr . ' 2>&1', $outputConvert, $retvalConvert);
            }
        }

        if (!is_file($tmpFileOcr . ".pdf")) {
            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'mercure',
                'level'     => 'ERROR',
                'tableName' => $tablename,
                'recordId'  => $aArgs['resId'],
                'eventType' => "OCR Tesseract - Error during OCR conversion",
                'eventId'   => "command : { $cmdConvertTiff }, output : { " . json_encode($outputConvert) . " }"
            ]);

            return [
                'errors' => '[OcrController] Error during conversion OCR',
                'output' => $outputConvert
            ];
        }

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'mercure',
            'level'     => 'INFO',
            'tableName' => $tablename,
            'recordId'  => $aArgs['resId'],
            'eventType' => "OCR Tesseract request success",
            'eventId'   => "document : {$tmpFileOcr}.pdf"
        ]);

        return ['output' => $outputConvert, 'return' => $retvalConvert, 'convertedFile' => $tmpFileOcr . ".pdf"];
    }
}
