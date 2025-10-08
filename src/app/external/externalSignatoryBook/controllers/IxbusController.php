<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Controller
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\controllers;

use Attachment\models\AttachmentModel;
use Attachment\models\AttachmentTypeModel;
use Convert\controllers\ConvertPdfController;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Exception;
use History\controllers\HistoryController;
use ExternalSignatoryBook\Infrastructure\DocumentLinkFactory;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use SrcCore\controllers\LogsController;
use SrcCore\models\CurlModel;
use SrcCore\models\TextFormatModel;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class IxbusController
{
    /**
     * The API expects hexadecimal identifiers for various manipulated objects (users, types, folders, etc.).
     * When these identifiers cannot be interpreted correctly, they generate generic errors
     * (which can occur in all API calls involving hexadecimal identifiers), such as the following:
     *
     *      - Impossible de trouver une correspondance à l'identifiant '{identifiant}' fourni
     *      - Impossible de trouver une correspondance à l'identifiant fourni
     */
    private const GENERIC_ERRORS_HEX_IDENTIFIERS = [
        'errorCode' => 1,
        'message'   => "Impossible de trouver une correspondance à l'identifiant"
    ];

    /**
     * @param $config
     *
     * @return array
     * @throws Exception
     */
    public static function getInitializeDatas($config): array
    {
        $curlResponse = CurlModel::exec([
            'url'     => rtrim($config['data']['url'], '/') . '/api/parapheur/v1/nature',
            'headers' => ['IXBUS_API:' . $config['data']['tokenAPI']],
            'method'  => 'GET'
        ]);

        if (!empty($curlResponse['response']['error'])) {
            return ['error' => $curlResponse['message']];
        }

        foreach ($curlResponse['response']['payload'] as $key => $value) {
            unset($curlResponse['response']['payload'][$key]['motClefs']);
        }
        return ['natures' => $curlResponse['response']['payload']];
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws Exception
     */
    public function getNatureDetails(Request $request, Response $response, array $args): Response
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if (empty($loadedXml)) {
            return $response->withStatus(500)->withJson([
                'errors' => 'remote signatory book: no configuration file found'
            ]);
        }
        $config = ['id' => (string)$loadedXml->signatoryBookEnabled];
        if ($config['id'] != 'ixbus') {
            return $response->withStatus(400)->withJson(['errors' => 'ixbus is disabled']);
        }
        foreach ($loadedXml->signatoryBook as $value) {
            if ($value->id == $config['id']) {
                $config['data'] = (array)$value;
                break;
            }
        }
        if (empty($config['data']['url'])) {
            return $response->withStatus(500)->withJson(['errors' => 'no ixbus url configured']);
        }

        $curlResponse = CurlModel::exec([
            'method'  => 'GET',
            'url'     => rtrim($config['data']['url'], '/') . '/api/parapheur/v1/circuit/' . $args['natureId'],
            'headers' => ['IXBUS_API:' . $config['data']['tokenAPI']]
        ]);

        if (empty($curlResponse['response']['payload']) || !empty($curlResponse['response']['error'])) {
            return $response->withStatus(500)->withJson([
                'errors' => $curlResponse['message'] ?? "HTTP {$curlResponse['code']} while contacting ixbus"
            ]);
        }

        foreach ($curlResponse['response']['payload'] as $key => $value) {
            unset($curlResponse['response']['payload'][$key]['etapes']);
            unset($curlResponse['response']['payload'][$key]['options']);
        }

        $return = ['messageModels' => $curlResponse['response']['payload']];

        $curlResponse = CurlModel::exec([
            'url'     => rtrim(
                $config['data']['url'],
                '/'
            ) . '/api/parapheur/v1/nature/' . $args['natureId'] .
                '/redacteur',
            'headers' => ['IXBUS_API:' . $config['data']['tokenAPI']],
            'method'  => 'GET'
        ]);

        if (empty($curlResponse['response']['payload']) || !empty($curlResponse['response']['error'])) {
            return $response->withStatus(500)->withJson([
                'errors' => $curlResponse['message'] ?? "HTTP {$curlResponse['code']} while contacting ixbus"
            ]);
        }

        $return['users'] = $curlResponse['response']['payload'];

        return $response->withJson($return);
    }

    /**
     * @param $args
     *
     * @return array
     * @throws Exception
     */
    public static function sendDatas($args): array
    {
        $mainResource = ResModel::getById([
            'resId'  => $args['resIdMaster'],
            'select' => [
                'res_id',
                'subject',
                'path',
                'filename',
                'docserver_id',
                'format',
                'category_id',
                'external_id',
                'integrations',
                'process_limit_date',
                'fingerprint'
            ]
        ]);

        $mainDocumentFilePath = null;
        if (!empty($mainResource['docserver_id'])) {
            $adrMainInfo = ConvertPdfController::getConvertedPdfById([
                'resId'  => $args['resIdMaster'],
                'collId' => 'letterbox_coll'
            ]);
            $letterboxPath = DocserverModel::getByDocserverId([
                'docserverId' => $adrMainInfo['docserver_id'],
                'select'      => ['path_template']
            ]);
            $mainDocumentFilePath = $letterboxPath['path_template'] .
                str_replace('#', '/', $adrMainInfo['path']) . $adrMainInfo['filename'];
        }

        $attachments = AttachmentModel::get([
            'select' => [
                'res_id',
                'title',
                'identifier',
                'attachment_type',
                'status',
                'typist',
                'docserver_id',
                'path',
                'filename',
                'creation_date',
                'validation_date',
                'relation',
                'origin_id',
                'fingerprint',
                'format'
            ],
            'where'  => [
                "res_id_master = ?",
                "attachment_type not in (?)",
                "status not in ('DEL', 'OBS', 'FRZ', 'TMP', 'SEND_MASS')",
                "in_signature_book = 'true'"
            ],
            'data'   => [$args['resIdMaster'], ['incoming_mail_attachment', 'signed_response']]
        ]);

        $annexes = [];
        $attachmentTypes = AttachmentTypeModel::get(['select' => ['type_id', 'signable']]);
        $attachmentTypes = array_column($attachmentTypes, 'signable', 'type_id');
        foreach ($attachments as $key => $value) {
            if (!$attachmentTypes[$value['attachment_type']]) {
                $adrInfo = ConvertPdfController::getConvertedPdfById([
                    'resId'  => $value['res_id'],
                    'collId' => 'attachments_coll'
                ]);
                if (
                    empty($adrInfo['docserver_id']) ||
                    strtolower(pathinfo($adrInfo['filename'], PATHINFO_EXTENSION)) != 'pdf'
                ) {
                    return ['error' => 'Attachment ' . $value['res_id'] . ' is not converted in pdf'];
                }
                $docserverInfo = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
                if (empty($docserverInfo['path_template'])) {
                    return ['error' => 'Docserver does not exist ' . $adrInfo['docserver_id']];
                }
                $filePath = $docserverInfo['path_template'] . str_replace('#', '/', $adrInfo['path']) .
                    $adrInfo['filename'];
                $docserverType = DocserverTypeModel::getById([
                    'id'     => $docserverInfo['docserver_type_id'],
                    'select' => ['fingerprint_mode']
                ]);
                $fingerprint = StoreController::getFingerPrint([
                    'filePath' => $filePath,
                    'mode'     => $docserverType['fingerprint_mode']
                ]);
                if ($adrInfo['fingerprint'] != $fingerprint) {
                    return ['error' => 'Fingerprints do not match'];
                }

                $annexes[] = ['filePath' => $filePath, 'fileName' => $value['title'] . '.pdf'];
                unset($attachments[$key]);
            }
        }

        $attachmentToFreeze = [];

        if (empty($mainResource['process_limit_date'])) {
            $processLimitDate = date('Y-m-d', strtotime(date("Y-m-d") . ' + 14 days'));
        } else {
            $processLimitDateTmp = explode(" ", $mainResource['process_limit_date']);
            $processLimitDate = $processLimitDateTmp[0];
        }

        if (!empty($mainDocumentFilePath)) {
            $annexes = array_merge($annexes, [
                [
                    'filePath' => $mainDocumentFilePath,
                    'fileName' => TextFormatModel::formatFilename([
                            'filename'  => $mainResource['subject'],
                            'maxLength' => 250
                        ]) . '.pdf'
                ]
            ]);
        }

        $signature = $args['manSignature'] == 'manual' ? 1 : 0;
        $bodyData = [
            'nature'     => $args['natureId'],
            'referent'   => $args['referent'],
            'circuit'    => $args['messageModel'],
            'options'    => [
                'confidentiel'                 => false,
                'dateLimite'                   => true,
                'documentModifiable'           => true,
                'annexesSignables'             => false,
                'autoriserModificationAnnexes' => true,
                'signature'                    => $signature
            ],
            'dateLimite' => $processLimitDate,
        ];

        foreach ($attachments as $value) {
            $resId = $value['res_id'];
            $collId = 'attachments_coll';

            $adrInfo = [
                'docserver_id' => $value['docserver_id'],
                'path'         => $value['path'],
                'filename'     => $value['filename'],
                'fingerprint'  => $value['fingerprint']
            ];

            if (!empty($args['config']['optionSendOfficeDocument'] ?? null)) {
                $adrInfo = ConvertPdfController::getConvertedPdfById(['resId' => $resId, 'collId' => $collId]);
            }

            $docserverInfo = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
            $filePath = $docserverInfo['path_template'] . str_replace('#', '/', $adrInfo['path']) .
                $adrInfo['filename'];

            $docserverType = DocserverTypeModel::getById([
                'id'     => $docserverInfo['docserver_type_id'],
                'select' => ['fingerprint_mode']
            ]);
            $fingerprint = StoreController::getFingerPrint([
                'filePath' => $filePath,
                'mode'     => $docserverType['fingerprint_mode']
            ]);
            if ($adrInfo['fingerprint'] != $fingerprint) {
                return ['error' => 'Fingerprints do not match'];
            }

            $bodyData['nom'] = str_replace(["\r\n", "\n", "\r"], " ", $value['title']);

            $createdFile = IxBusController::createFolder(['config' => $args['config'], 'body' => $bodyData]);
            if (!empty($createdFile['error'])) {
                return ['error' => $createdFile['message']];
            }
            $folderId = $createdFile['folderId'];

            $fileExtension = strtolower(pathinfo($adrInfo['filename'], PATHINFO_EXTENSION));
            $fileExtensionLength = strlen($fileExtension);

            $addedFile = IxBusController::addFileToFolder([
                'config'   => $args['config'],
                'folderId' => $folderId,
                'filePath' => $filePath,
                'fileName' => TextFormatModel::formatFilename([
                        'filename'  => $value['title'],
                        'maxLength' => 250 - $fileExtensionLength
                    ]) . ".$fileExtension",
                'fileType' => 'principal'
            ]);
            if (!empty($addedFile['error'])) {
                return ['error' => $addedFile['error']];
            }

            foreach ($annexes as $annexeFile) {
                $addedFile = IxBusController::addFileToFolder([
                    'config'   => $args['config'],
                    'folderId' => $folderId,
                    'filePath' => $annexeFile['filePath'],
                    'fileName' => $annexeFile['fileName'],
                    'fileType' => 'annexe'
                ]);
                if (!empty($addedFile['error'])) {
                    return ['error' => $addedFile['error']];
                }
            }

            $transmittedFolder = IxBusController::transmitFolder([
                'config'   => $args['config'],
                'folderId' => $folderId
            ]);
            if (!empty($transmittedFolder['error'])) {
                return ['error' => $transmittedFolder['error']];
            }

            $attachmentToFreeze[$collId][$resId] = $folderId;
        }

        // Send main document if in signature book
        $mainDocumentIntegration = json_decode($mainResource['integrations'], true);
        $externalId = json_decode($mainResource['external_id'], true);
        if ($mainDocumentIntegration['inSignatureBook'] && empty($externalId['signatureBookId'])) {
            $resId = $mainResource['res_id'];
            $collId = 'letterbox_coll';

            $adrInfo = [
                'docserver_id' => $mainResource['docserver_id'],
                'path'         => $mainResource['path'],
                'filename'     => $mainResource['filename'],
                'fingerprint'  => $mainResource['fingerprint']
            ];

            if (!empty($args['config']['optionSendOfficeDocument'] ?? null)) {
                $adrInfo = ConvertPdfController::getConvertedPdfById(['resId' => $resId, 'collId' => $collId]);
            }

            $docserverInfo = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
            $filePath = $docserverInfo['path_template'] . str_replace('#', '/', $adrInfo['path']) .
                $adrInfo['filename'];

            $docserverType = DocserverTypeModel::getById([
                'id'     => $docserverInfo['docserver_type_id'],
                'select' => ['fingerprint_mode']
            ]);
            $fingerprint = StoreController::getFingerPrint([
                'filePath' => $filePath,
                'mode'     => $docserverType['fingerprint_mode']
            ]);
            if ($adrInfo['fingerprint'] != $fingerprint) {
                return ['error' => 'Fingerprints do not match'];
            }

            $fileExtension = strtolower(pathinfo($adrInfo['filename'], PATHINFO_EXTENSION));
            $fileExtensionLength = strlen($fileExtension);

            $bodyData['nom'] = str_replace(["\r\n", "\n", "\r"], " ", $mainResource['subject']);
            $fileName = TextFormatModel::formatFilename([
                    'filename'  => $mainResource['subject'],
                    'maxLength' => 250 - $fileExtensionLength
                ]) . ".$fileExtension";

            $createdFile = IxBusController::createFolder(['config' => $args['config'], 'body' => $bodyData]);
            if (!empty($createdFile['error'])) {
                return ['error' => $createdFile['message']];
            }
            $folderId = $createdFile['folderId'];

            $addedFile = IxBusController::addFileToFolder([
                'config'   => $args['config'],
                'folderId' => $folderId,
                'filePath' => $filePath,
                'fileName' => $fileName,
                'fileType' => 'principal'
            ]);
            if (!empty($addedFile['error'])) {
                return ['error' => $addedFile['error']];
            }

            $annexes = array_filter($annexes, function ($attachment) use ($filePath, $fileName) {
                return !($attachment['filePath'] === $filePath && $attachment['fileName'] === $fileName);
            });

            foreach ($annexes as $annexeFile) {
                $addedFile = IxBusController::addFileToFolder([
                    'config'   => $args['config'],
                    'folderId' => $folderId,
                    'filePath' => $annexeFile['filePath'],
                    'fileName' => $annexeFile['fileName'],
                    'fileType' => 'annexe'
                ]);
                if (!empty($addedFile['error'])) {
                    return ['error' => $addedFile['error']];
                }
            }

            $transmittedFolder = IxBusController::transmitFolder([
                'config'   => $args['config'],
                'folderId' => $folderId
            ]);
            if (!empty($transmittedFolder['error'])) {
                return ['error' => $transmittedFolder['error']];
            }

            $attachmentToFreeze[$collId][$resId] = $folderId;
        }

        return ['sended' => $attachmentToFreeze];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     */
    public static function createFolder(array $args): array
    {
        $curlResponse = CurlModel::exec([
            'url'     => rtrim($args['config']['data']['url'], '/') . '/api/parapheur/v1/dossier',
            'headers' => ['content-type:application/json', 'IXBUS_API:' . $args['config']['data']['tokenAPI']],
            'method'  => 'POST',
            'body'    => json_encode($args['body'])
        ]);
        if (!empty($curlResponse['response']['error'])) {
            return ['error' => $curlResponse['response']['message']];
        }

        return ['folderId' => $curlResponse['response']['payload']['identifiant']];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     */
    public static function addFileToFolder(array $args): array
    {
        $curlResponse = CurlModel::exec([
            'url'           => rtrim($args['config']['data']['url'], '/') . '/api/parapheur/v1/document/' .
                $args['folderId'],
            'headers'       => ['IXBUS_API:' . $args['config']['data']['tokenAPI']],
            'customRequest' => 'POST',
            'method'        => 'CUSTOM',
            'body'          => [
                'fichier' => CurlModel::makeCurlFile([
                    'path' => $args['filePath'],
                    'name' => $args['fileName']
                ]),
                'type'    => $args['fileType']
            ]
        ]);
        if (!empty($curlResponse['response']['error'])) {
            return ['error' => $curlResponse['response']['message']];
        }

        return [];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     */
    public static function transmitFolder(array $args): array
    {
        $curlResponse = CurlModel::exec([
            'url'     => rtrim($args['config']['data']['url'], '/') . '/api/parapheur/v1/dossier/' .
                $args['folderId'] . '/transmettre',
            'headers' => ['IXBUS_API:' . $args['config']['data']['tokenAPI']],
            'method'  => 'POST',
            'body'    => '{}'
        ]);
        if (!empty($curlResponse['response']['error'])) {
            return ['error' => $curlResponse['response']['message']];
        }

        return [];
    }

    /**
     * @param $args
     *
     * @return array
     * @throws Exception
     */
    public static function retrieveSignedMails($args): array
    {
        $version = $args['version'];
        foreach ($args['idsToRetrieve'][$version] as $resId => $value) {
            $folderData = IxbusController::getDossier([
                'config'   => $args['config'],
                'folderId' => $value['external_id']
            ]);

            if (!empty($folderData['error'])) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => $GLOBALS['moduleId'],
                    'level'     => 'ERROR',
                    'tableName' => $GLOBALS['batchName'],
                    'eventType' => 'script',
                    'eventId'   => "[ixbus api] Cannot fetch folder : {$folderData['error']['message']}"
                ]);

                if (
                    $folderData['error']['errorCode'] == IxbusController::GENERIC_ERRORS_HEX_IDENTIFIERS['errorCode'] &&
                    str_contains(
                        $folderData['error']['message'],
                        IxbusController::GENERIC_ERRORS_HEX_IDENTIFIERS['message']
                    )
                ) {
                    $documentLink = DocumentLinkFactory::createDocumentLink();
                    try {
                        $type = $version == 'resLetterbox' ? 'resource' : 'attachment';
                        $title = $version == 'resLetterbox' ? $value['subject'] : $value['title'];
                        $documentLink->removeExternalLink($value['res_id'], $title, $type, $value['external_id']);
                    } catch (Throwable $th) {
                        $info = "[SCRIPT] Failed to remove document link: MaarchCourrier docId {$value['res_id']}, "
                            . "document type $type, parapheur docId {$value['external_id']}";
                        LogsController::add([
                            'isTech'    => true,
                            'moduleId'  => $GLOBALS['moduleId'],
                            'level'     => 'ERROR',
                            'tableName' => $GLOBALS['batchName'],
                            'eventType' => 'script',
                            'eventId'   => "$info. Error: {$th->getMessage()}."
                        ]);
                    }
                }

                unset($args['idsToRetrieve'][$version][$resId]);
                continue;
            }

            if (in_array($folderData['data']['etat'], ['Refusé', 'Terminé'])) {
                $args['idsToRetrieve'][$version][$resId]['status'] = $folderData['data']['etat'] ==
                'Refusé' ? 'refused' : 'validated';
                $signedDocument = IxbusController::getDocument([
                    'config'     => $args['config'],
                    'documentId' => $folderData['data']['documents']['principal']['identifiant']
                ]);
                $args['idsToRetrieve'][$version][$resId]['format'] = 'pdf';
                $args['idsToRetrieve'][$version][$resId]['encodedFile'] = $signedDocument['encodedDocument'];
                if (!empty($folderData['data']['detailEtat'])) {
                    $args['idsToRetrieve'][$version][$resId]['notes'][] = [
                        'content' => $folderData['data']['detailEtat']
                    ];
                }

                if (is_array($folderData['data']['etapes']) && !empty($folderData['data']['etapes'])) {
                    $args['idsToRetrieve'][$version][$resId]['typist'] = null;
                    $args['idsToRetrieve'][$version][$resId]['signatory_user_serial_id'] = null;

                    $lastStep = count($folderData['data']['etapes']) - 1;
                    $prenom = $folderData['data']['etapes'][$lastStep]['utilisateurRealisation']['prenom'];
                    $nom = $folderData['data']['etapes'][$lastStep]['utilisateurRealisation']['nom'];
                    $signatoryUser = "$prenom $nom";

                    IxbusController::updateDocumentExternalStateSignatoryUser([
                        'id'            => $resId,
                        'type'          => ($version == 'resLetterbox' ? 'resource' : 'attachment'),
                        'signatoryUser' => $signatoryUser
                    ]);
                }
            } else {
                if (!empty($folderData['error'])) {
                    LogsController::add([
                        'isTech'    => true,
                        'moduleId'  => $GLOBALS['moduleId'],
                        'level'     => 'ERROR',
                        'tableName' => $GLOBALS['batchName'],
                        'eventType' => 'script',
                        'eventId'   => "[Ixbus api] {$folderData['error']}"
                    ]);

                    $userId = UserModel::get([
                        'select' => ['id'],
                        'where'  => ['mode = ? OR mode = ?'],
                        'data'   => ['root_visible', 'root_invisible'],
                        'limit'  => 1
                    ])[0]['id'];

                    HistoryController::add([
                        'tableName' => 'res_letterbox',
                        'recordId'  => $value['res_id_master'] ?? $value['res_id'],
                        'eventType' => 'ACTION#1',
                        'eventId'   => '1',
                        'userId'    => $userId,
                        'info'      => "[Ixbus api] {$folderData['error']}"
                    ]);
                }

                unset($args['idsToRetrieve'][$version][$resId]);
            }
        }

        // retourner seulement les mails récupérés (validés ou refusé)
        return $args['idsToRetrieve'];
    }

    /**
     * @param array $args
     *
     * @return void
     * @throws Exception
     */
    public static function updateDocumentExternalStateSignatoryUser(array $args): void
    {
        ValidatorModel::notEmpty($args, ['id', 'type', 'signatoryUser']);
        ValidatorModel::intType($args, ['id']);
        ValidatorModel::stringType($args, ['type', 'signatoryUser']);

        $signatoryUser = $args['signatoryUser'];

        if ($args['type'] == 'resource') {
            ResModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$args['id']],
                'postSet' => [
                    'external_state' => "jsonb_set(external_state::jsonb, '{signatoryUser}', '\"$signatoryUser\"')"
                ]
            ]);
        } else {
            AttachmentModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$args['id']],
                'postSet' => [
                    'external_state' => "jsonb_set(external_state::jsonb, '{signatoryUser}', '\"$signatoryUser\"')"
                ]
            ]);
        }
    }

    /**
     * @param $args
     *
     * @return array
     * @throws Exception
     */
    public static function getDossier($args): array
    {
        $curlResponse = CurlModel::exec([
            'url'     => rtrim($args['config']['data']['url'], '/') . '/api/parapheur/v1/dossier/' .
                $args['folderId'],
            'headers' => ['content-type:application/json', 'IXBUS_API:' . $args['config']['data']['tokenAPI']],
            'method'  => 'GET'
        ]);
        if (!empty($curlResponse['response']['error'])) {
            return ['error' => $curlResponse['response']];
        }

        return ['data' => $curlResponse['response']['payload']];
    }

    /**
     * @param $args
     *
     * @return array
     * @throws Exception
     */
    public static function getDocument($args): array
    {
        $curlResponse = CurlModel::exec([
            'url'     => rtrim($args['config']['data']['url'], '/') . '/api/parapheur/v1/document/contenu/' .
                $args['documentId'],
            'headers' => [
                'Accept: application/zip',
                'content-type:application/json',
                'IXBUS_API:' . $args['config']['data']['tokenAPI']
            ],
            'method'  => 'GET'
        ]);

        return ['encodedDocument' => base64_encode($curlResponse['response'])];
    }
}
