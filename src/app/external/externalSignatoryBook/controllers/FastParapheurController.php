<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief fastParapheur Controller
 * @author nathan.cheval@edissyum.com
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\controllers;

use Attachment\controllers\AttachmentTypeController;
use Attachment\models\AttachmentModel;
use Attachment\models\AttachmentTypeModel;
use Convert\controllers\ConvertPdfController;
use DateTimeImmutable;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Entity\models\ListInstanceModel;
use Exception;
use ExternalSignatoryBook\Infrastructure\DocumentLinkFactory;
use History\controllers\HistoryController;
use Resource\controllers\ResController;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use SimpleXMLElement;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\TextFormatModel;
use SrcCore\models\ValidatorModel;
use Throwable;
use User\controllers\UserController;
use Note\models\NoteModel;
use Entity\models\EntityModel;
use IndexingModel\models\IndexingModelFieldModel;
use Resource\controllers\SummarySheetController;
use setasign\Fpdi\Tcpdf\Fpdi;
use Convert\models\AdrModel;
use User\models\UserModel;
use ZipArchive;


/**
 * @codeCoverageIgnore
 */
class FastParapheurController
{
    public const INVALID_DOC_ID_ERROR = "Internal error: Invalid docId";
    public const INVALID_DOC_ID_TYPE_ERROR = "Internal error: Failed to convert value of type 'java.lang.String' 
    to required type 'long'";
    /**
     * Dimensions are in mm.
     */
    private const PICTOGRAM_DIMENSIONS = [
        'height' => 20,
        'width'  => 50,
    ];

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function getWorkflowDetails(Request $request, Response $response): Response
    {
        $config = FastParapheurController::getConfig();
        if (!empty($config['errors'])) {
            return $response->withStatus($config['code'])->withJson(['errors' => $config['errors']]);
        }

        $signatureModes = FastParapheurController::getSignatureModes(['mapping' => true]);
        if (!empty($signatureModes['errors'])) {
            return $response->withStatus((int)$signatureModes['code'])->withJson(
                ['errors' => $signatureModes['errors']]
            );
        }

        $optionOtp = false;
        if (filter_var($config['optionOtp'], FILTER_VALIDATE_BOOLEAN)) {
            $optionOtp = true;
        }

        return $response->withJson([
            'workflowTypes'  => $config['workflowTypes']['type'],
            'otpStatus'      => $optionOtp,
            'signatureModes' => $signatureModes['signatureModes']
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function linkUserToFastParapheur(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();
        if (!Validator::notEmpty()->email()->validate($body['fastParapheurUserEmail'] ?? null)) {
            return $response->withStatus(400)->withJson(
                ['errors' => 'body fastParapheurUserEmail is not a valid email address']
            );
        }
        if (!Validator::notEmpty()->intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'args id is not an integer']);
        }

        $userController = new UserController();
        $error = $userController->hasUsersRights(['id' => $args['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $alreadyLinked = UserModel::get([
            'select' => [1],
            'where'  => ['external_id->>\'fastParapheur\' = ?'],
            'data'   => [$body['fastParapheurUserEmail']]
        ]);
        if (!empty($alreadyLinked)) {
            return $response->withStatus(403)->withJson(
                [
                    'errors' => 'FastParapheur user email can only be linked to a single MaarchCourrier user',
                    'lang'   => 'fastParapheurUserAlreadyLinked'
                ]
            );
        }

        $check = FastParapheurController::checkUserExistanceInFastParapheur(
            ['fastParapheurUserEmail' => $body['fastParapheurUserEmail']]
        );
        if (!empty($check['errors'])) {
            return $response->withStatus($check['code'])->withJson(['errors' => $check['errors']]);
        }

        $userInfo = UserModel::getById(['select' => ['external_id', 'firstname', 'lastname'], 'id' => $args['id']]);
        $externalId = json_decode($userInfo['external_id'], true);
        $externalId['fastParapheur'] = $body['fastParapheurUserEmail'];

        UserModel::updateExternalId(['id' => $args['id'], 'externalId' => json_encode($externalId)]);

        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $GLOBALS['id'],
            'eventType' => 'UP',
            'eventId'   => 'userModification',
            'info'      => _USER_LINKED_TO_FASTPARAPHEUR . " : {$userInfo['firstname']} {$userInfo['lastname']}"
        ]);

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function unlinkUserToFastParapheur(Request $request, Response $response, array $args): Response
    {
        if (!Validator::notEmpty()->intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'args id is not an integer']);
        }

        $userController = new UserController();
        $error = $userController->hasUsersRights(['id' => $args['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $user = UserModel::getById(['id' => $args['id'], 'select' => ['firstname', 'lastname', 'external_id']]);
        $externalId = json_decode($user['external_id'], true);
        unset($externalId['fastParapheur']);

        UserModel::updateExternalId(['id' => $args['id'], 'externalId' => json_encode($externalId)]);

        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $GLOBALS['id'],
            'eventType' => 'UP',
            'eventId'   => 'userModification',
            'info'      => _USER_UNLINKED_TO_FASTPARAPHEUR . " : {$user['firstname']} {$user['lastname']}"
        ]);

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function userStatusInFastParapheur(Request $request, Response $response, array $args): Response
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if ($loadedXml->signatoryBookEnabled != 'fastParapheur') {
            return $response->withStatus(403)->withJson(['errors' => 'fastParapheur is not enabled']);
        }

        $user = UserModel::getById(
            ['id' => $args['id'], 'select' => ['external_id->>\'fastParapheur\' as "fastParapheurId"']]
        );
        if (empty($user['fastParapheurId'])) {
            return $response->withStatus(403)->withJson(
                ['errors' => 'user does not have a Fast Parapheur email']
            );
        }

        $check = FastParapheurController::checkUserExistanceInFastParapheur(
            ['fastParapheurUserEmail' => $user['fastParapheurId']]
        );
        if (!empty($check['errors'])) {
            return $response->withStatus($check['code'])->withJson(['errors' => $check['errors']]);
        }

        return $response->withJson(['link' => $user['fastParapheurId']]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function getWorkflow(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();

        if (!empty($queryParams['type']) && $queryParams['type'] == 'resource') {
            if (!ResController::hasRightByResId(['resId' => [$args['id']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(400)->withJson(['errors' => 'Resource out of perimeter']);
            }
            $resource = ResModel::getById(['resId' => $args['id'], 'select' => ['external_id', 'external_state']]);
            if (empty($resource)) {
                return $response->withStatus(400)->withJson(['errors' => 'Resource does not exist']);
            }
            $resource['resourceType'] = 'letterbox_coll';
        } else {
            $resource = AttachmentModel::getById(
                ['id' => $args['id'], 'select' => ['res_id_master', 'external_id', 'external_state']]
            );
            if (empty($resource)) {
                return $response->withStatus(400)->withJson(['errors' => 'Attachment does not exist']);
            }
            if (
                !ResController::hasRightByResId(['resId' => [$resource['res_id_master']], 'userId' => $GLOBALS['id']])
            ) {
                return $response->withStatus(400)->withJson(['errors' => 'Resource does not exist']);
            }
            $resource['resourceType'] = 'attachments_coll';
        }

        $externalId = json_decode($resource['external_id'], true);
        if (empty($externalId['signatureBookId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Resource is not linked to Fast Parapheur']);
        }

        $externalState = json_decode($resource['external_state'], true);
        $fetchDate = new DateTimeImmutable($externalState['signatureBookWorkflow']['fetchDate']);
        $timeAgo = new DateTimeImmutable('-30 minutes');

        if (
            !empty($externalState['signatureBookWorkflow']['fetchDate']) &&
            $fetchDate->getTimestamp() >= $timeAgo->getTimestamp()
        ) {
            return $response->withJson($externalState['signatureBookWorkflow']['data']);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if (empty($loadedXml)) {
            return $response->withStatus(400)->withJson(
                ['errors' => 'SignatoryBooks configuration file missing']
            );
        }

        $fastParapheurBlock = $loadedXml->xpath('//signatoryBook[id=\'fastParapheur\']')[0] ?? null;
        if (empty($fastParapheurBlock)) {
            return $response->withStatus(500)->withJson(['errors' => 'invalid configuration for FastParapheur']);
        }
        $url = (string)$fastParapheurBlock->url;
        $certPath = (string)$fastParapheurBlock->certPath;
        $certPass = (string)$fastParapheurBlock->certPass;
        $certType = (string)$fastParapheurBlock->certType;
        $subscriberId = (string)$fastParapheurBlock->subscriberId;

        $curlReturn = CurlModel::exec([
            'url'     => $url . '/documents/v2/' . $externalId['signatureBookId'] . '/history',
            'method'  => 'GET',
            'options' => [
                CURLOPT_SSLCERT       => $certPath,
                CURLOPT_SSLCERTPASSWD => $certPass,
                CURLOPT_SSLCERTTYPE   => $certType
            ]
        ]);

        if ($curlReturn['code'] != 200) {
            return $response->withStatus($curlReturn['code'])->withJson($curlReturn['errors']);
        }

        if (!empty($curlReturn)) {
            $fastParapheurUsers = FastParapheurController::getUsers([
                'config' => [
                    'subscriberId' => $subscriberId,
                    'url'          => $url,
                    'certPath'     => $certPath,
                    'certPass'     => $certPass,
                    'certType'     => $certType
                ]
            ]);
            $fastParapheurUsers = array_column($fastParapheurUsers, 'email', 'idToDisplay');
        }

        $externalWorkflow = [];
        $order = 0;
        $mode = null;
        foreach ($curlReturn['response'] as $step) {
            if (mb_stripos($step['stateName'], 'Préparé') === 0) {
                continue;
            }
            if (empty($step['userFullname'])) {
                $mode = mb_stripos($step['stateName'], 'visa') !== false ? 'visa' : 'sign';
                continue;
            }
            $order++;
            $user = UserModel::get([
                'select' => [
                    'id',
                    'concat(firstname, \' \', lastname) as name',
                ],
                'where'  => ['external_id->>\'fastParapheur\' = ?'],
                'data'   => [$fastParapheurUsers[$step['userFullname']]],
                'limit'  => 1
            ]);
            if (empty($user)) {
                $user = ['id' => null, 'name' => '-'];
            } else {
                $user = $user[0];
            }
            $processDate = new DateTimeImmutable($step['date']);
            $externalWorkflow[] = [
                'userId'       => $user['id'],
                'userDisplay'  => $step['userFullname'] . ' (' . $user['name'] . ')',
                'mode'         => $mode,
                'order'        => $order,
                'process_date' => $processDate->format('d-m-Y H:i')
            ];
        }

        $currentDate = new DateTimeImmutable();
        $externalState['signatureBookWorkflow']['fetchDate'] = $currentDate->format('c');
        $externalState['signatureBookWorkflow']['data'] = $externalWorkflow;
        if ($resource['resourceType'] == 'letterbox_coll') {
            ResModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$args['id']],
                'postSet' => [
                    'external_state' => 'jsonb_set(external_state, \'{signatureBookWorkflow}\', \'' .
                        json_encode($externalState['signatureBookWorkflow']) . '\'::jsonb)'
                ]
            ]);
        } else {
            AttachmentModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$args['id']],
                'postSet' => [
                    'external_state' => 'jsonb_set(external_state, \'{signatureBookWorkflow}\', \'' .
                        json_encode($externalState['signatureBookWorkflow']) . '\'::jsonb)'
                ]
            ]);
        }

        return $response->withJson($externalWorkflow);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function retrieveSignedMails(array $args): array
    {
        $version = $args['version'];

        $fastUsers = [];
        if (
            !empty($args['config']['data']['integratedWorkflow']) &&
            $args['config']['data']['integratedWorkflow'] == 'true'
        ) {
            // Get all fast users, format them to have the email and name (as formatted in fast's /history route)
            $fastUsers = FastParapheurController::getUsers(['config' => $args['config']['data'], 'noFormat' => true]);
            $fastUsers = array_map(function ($user) {
                return [
                    'email' => $user['email'],
                    'name'  => $user['nom'] . ' ' . $user['prenom']
                ];
            }, $fastUsers);
            $fastUsers = array_column($fastUsers, 'name', 'email');
        }

        foreach ($args['idsToRetrieve'][$version] as $resId => $value) {
            if (empty($value['res_id_master'])) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => $GLOBALS['moduleId'],
                    'level'     => 'INFO',
                    'tableName' => $GLOBALS['batchName'],
                    'eventType' => 'script',
                    'eventId'   => "Retrieve main document resId: $resId"
                ]);
            } else {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => $GLOBALS['moduleId'],
                    'level'     => 'INFO',
                    'tableName' => $GLOBALS['batchName'],
                    'eventType' => 'script',
                    'eventId'   => "Retrieve attachment resId: $resId"
                ]);
            }
            if (empty(trim($value['external_id']))) {
                $args['idsToRetrieve'][$version][$resId]['status'] = 'waiting';
                continue;
            }

            if (!empty($value['external_state_fetch_date'])) {
                $fetchDate = new DateTimeImmutable($value['external_state_fetch_date']);
                $timeAgo = new DateTimeImmutable('-30 minutes');

                if ($fetchDate->getTimestamp() >= $timeAgo->getTimestamp()) {
                    $newDate = $fetchDate->modify('+30 minutes');

                    LogsController::add([
                        'isTech'    => true,
                        'moduleId'  => $GLOBALS['moduleId'],
                        'level'     => 'INFO',
                        'tableName' => $GLOBALS['batchName'],
                        'eventType' => 'script',
                        'eventId'   => "Time limit reached ! Next retrieve time : {$newDate->format('d-m-Y H:i')}"
                    ]);

                    unset($args['idsToRetrieve'][$version][$resId]);
                    continue;
                }
            }

            $historyResponse = FastParapheurController::getDocumentHistory(
                ['config' => $args['config'], 'documentId' => $value['external_id']]
            );

            // Update external_state_fetch_date event if $historyResponse return an error.
            // To avoid spamming the API endpoint.
            $updateHistoryFetchDate = FastParapheurController::updateFetchHistoryDateByExternalId([
                'type'  => ($version == 'resLetterbox' ? 'resource' : 'attachment'),
                'resId' => $value['res_id']
            ]);
            if (!empty($updateHistoryFetchDate['errors'])) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => $GLOBALS['moduleId'],
                    'level'     => 'ERROR',
                    'tableName' => $GLOBALS['batchName'],
                    'eventType' => 'script',
                    'eventId'   => "{$updateHistoryFetchDate['errors']}"
                ]);
                unset($args['idsToRetrieve'][$version][$resId]);
                continue;
            }

            // Check for $historyResponse error
            if (!empty($historyResponse['errors'])) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => $GLOBALS['moduleId'],
                    'level'     => 'ERROR',
                    'tableName' => $GLOBALS['batchName'],
                    'eventType' => 'script',
                    'eventId'   => "[fastParapheur api] {$historyResponse['errors']}"
                ]);

                if (
                    $historyResponse['errors'] == FastParapheurController::INVALID_DOC_ID_ERROR ||
                    str_contains($historyResponse['errors'], FastParapheurController::INVALID_DOC_ID_TYPE_ERROR)
                ) {
                    $documentLink = DocumentLinkFactory::createDocumentLink();
                    try {
                        $type = $version == 'resLetterbox' ? 'resource' : 'attachment';
                        $title = $version == 'resLetterbox' ? $value['subject'] : $value['title'];
                        $documentLink->removeExternalLink($value['res_id'], $title, $type, $value['external_id']);
                    } catch (Throwable $th) {
                        $info = "[SCRIPT] Failed to remove document link: MaarchCourrier docId {$value['res_id']}, ";
                        $info .= "document type $type, parapheur docId {$value['external_id']}";
                        LogsController::add([
                            'isTech'    => true,
                            'moduleId'  => $GLOBALS['moduleId'],
                            'level'     => 'ERROR',
                            'tableName' => $GLOBALS['batchName'],
                            'eventType' => 'script',
                            'eventId'   => "$info. Error: {$th->getMessage()}."
                        ]);
                    }
                } else {
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
                        'info'      => "[fastParapheur api] {$historyResponse['errors']}"
                    ]);
                }

                unset($args['idsToRetrieve'][$version][$resId]);
                continue;
            }

            if (
                !empty($args['config']['data']['integratedWorkflow']) &&
                $args['config']['data']['integratedWorkflow'] == 'true'
            ) {
                if (empty($value['res_id_master'])) {
                    $resource = ResModel::getById([
                        'select' => ['external_state'],
                        'resId'  => $resId
                    ]);
                } else {
                    $resource = AttachmentModel::getById([
                        'select' => ['external_state'],
                        'id'     => $resId
                    ]);
                }
                $externalState = json_decode($resource['external_state'] ?? '{}', true);
                $knownWorkflow = array_map(function ($step) use ($fastUsers) {
                    if ($step['type'] == 'externalOTP') {
                        $step['name'] = $step['lastname'] . " " . $step['firstname'];
                    } else {
                        $step['name'] = $fastUsers[$step['id']];
                    }
                    return $step;
                }, $externalState['signatureBookWorkflow']['workflow'] ?? []);

                $lastFastWorkflowAction = FastParapheurController::getLastFastWorkflowAction(
                    $historyResponse['response'],
                    $knownWorkflow,
                    $args['config']['data']
                );
                if (empty($lastFastWorkflowAction)) {
                    $args['idsToRetrieve'][$version][$resId]['status'] = 'waiting';
                    continue;
                }
                $historyResponse['response'] = [
                    $lastFastWorkflowAction
                ];
            }

            $validatedState = $args['config']['data']['validatedState'] ?? null;
            $validatedVisaState = $args['config']['data']['validatedVisaState'] ?? null;
            $refusedState = $args['config']['data']['refusedState'] ?? null;
            $refusedVisaState = $args['config']['data']['refusedVisaState'] ?? null;
            // Loop on all steps of the documents (prepared, send to signature, signed etc...)
            foreach ($historyResponse['response'] as $valueResponse) {
                $signatoryInfo = FastParapheurController::getSignatoryUserInfo([
                    'config'        => $args['config'],
                    'valueResponse' => $valueResponse,
                    'resId'         => $args['idsToRetrieve'][$version][$resId]['res_id_master'] ??
                        $args['idsToRetrieve'][$version][$resId]['res_id']
                ]);

                if (
                    $valueResponse['stateName'] == $validatedState ||
                    $valueResponse['stateName'] == $validatedVisaState
                ) {
                    LogsController::add([
                        'isTech'    => true,
                        'moduleId'  => $GLOBALS['moduleId'],
                        'level'     => 'INFO',
                        'tableName' => $GLOBALS['batchName'],
                        'eventType' => 'script',
                        'eventId'   => "Circuit ended ! Retrieve file from fastParapheur"
                    ]);
                    $response = FastParapheurController::download(
                        ['config' => $args['config'], 'documentId' => $value['external_id']]
                    );
                    $args['idsToRetrieve'][$version][$resId]['status'] = 'validated';
                    $args['idsToRetrieve'][$version][$resId]['format'] = 'pdf';

                    $args['idsToRetrieve'][$version][$resId]['encodedFile'] = $response['b64FileContent'];
                    $args['idsToRetrieve'][$version][$resId]['signatory_user_serial_id'] = null;

                    $proofDocument = FastParapheurController::makeHistoryProof([
                        'documentId'      => $value['external_id'],
                        'config'          => $args['config'],
                        'historyData'     => $historyResponse['response'],
                        'filename'        => ($args['idsToRetrieve'][$version][$resId]['title'] ??
                                $args['idsToRetrieve'][$version][$resId]['subject']) . '.pdf',
                        'signEncodedFile' => $response['b64FileContent']
                    ]);
                    if (!empty($proofDocument['errors'])) {
                        LogsController::add([
                            'isTech'    => true,
                            'moduleId'  => $GLOBALS['moduleId'],
                            'level'     => 'ERROR',
                            'tableName' => $GLOBALS['batchName'],
                            'eventType' => 'script',
                            'eventId'   => "{$proofDocument['errors']}"
                        ]);
                        continue;
                    } elseif (!empty($proofDocument['encodedProofDocument'])) {
                        LogsController::add([
                            'isTech'    => true,
                            'moduleId'  => $GLOBALS['moduleId'],
                            'level'     => 'INFO',
                            'tableName' => $GLOBALS['batchName'],
                            'eventType' => 'script',
                            'eventId'   => "Retrieve proof from fastParapheur"
                        ]);
                        $args['idsToRetrieve'][$version][$resId]['log'] = $proofDocument['encodedProofDocument'];
                        $args['idsToRetrieve'][$version][$resId]['logFormat'] = $proofDocument['format'];
                        $args['idsToRetrieve'][$version][$resId]['logTitle'] = '[Faisceau de preuve]';
                    }

                    if (
                        empty($args['config']['data']['integratedWorkflow']) ||
                        $args['config']['data']['integratedWorkflow'] == 'false'
                    ) {
                        $args['idsToRetrieve'][$version][$resId]['signatory_user_serial_id'] = $signatoryInfo['id'] ??
                            null;
                    } elseif (!empty($valueResponse['userFastId'] ?? null) && $signatoryInfo['id']) {
                        $args['idsToRetrieve'][$version][$resId]['signatory_user_serial_id'] = $signatoryInfo['id'];
                        $args['idsToRetrieve'][$version][$resId]['typist'] = $signatoryInfo['id'];
                    } else {
                        $args['idsToRetrieve'][$version][$resId]['signatory_user_serial_id'] = null;
                        $args['idsToRetrieve'][$version][$resId]['typist'] = null;
                        FastParapheurController::updateDocumentExternalStateSignatoryUser([
                            'id'            => $resId,
                            'type'          => ($version == 'resLetterbox' ? 'resource' : 'attachment'),
                            'signatoryUser' => $signatoryInfo['name'] ?? $valueResponse['userFullname']
                        ]);
                    }
                    LogsController::add([
                        'isTech'    => true,
                        'moduleId'  => $GLOBALS['moduleId'],
                        'level'     => 'INFO',
                        'tableName' => $GLOBALS['batchName'],
                        'eventType' => 'script',
                        'eventId'   => "Done!"
                    ]);
                    break;
                } elseif (
                    $valueResponse['stateName'] == $refusedState ||
                    $valueResponse['stateName'] == $refusedVisaState
                ) {
                    $response = FastParapheurController::getRefusalMessage([
                        'config'     => $args['config'],
                        'documentId' => $value['external_id'],
                        'res_id'     => $resId,
                        'version'    => $version
                    ]);
                    $args['idsToRetrieve'][$version][$resId]['status'] = 'refused';
                    if (
                        empty($args['config']['data']['integratedWorkflow']) ||
                        $args['config']['data']['integratedWorkflow'] == 'false'
                    ) {
                        $args['idsToRetrieve'][$version][$resId]['notes'][] = [
                            'content' => $signatoryInfo['lastname'] . ' ' . $signatoryInfo['firstname'] . ' : ' .
                                $response
                        ];
                    } elseif ($signatoryInfo['id']) {
                        $args['idsToRetrieve'][$version][$resId]['notes'][] = [
                            'content' => $signatoryInfo['name'] . ' : ' . $response
                        ];
                    } else {
                        $args['idsToRetrieve'][$version][$resId]['notes'][] = [
                            'content' => $valueResponse['userFullname'] . ' : ' . $response
                        ];
                    }
                    LogsController::add([
                        'isTech'    => true,
                        'moduleId'  => $GLOBALS['moduleId'],
                        'level'     => 'INFO',
                        'tableName' => $GLOBALS['batchName'],
                        'eventType' => 'script',
                        'eventId'   => "Done!"
                    ]);
                    break;
                } else {
                    $args['idsToRetrieve'][$version][$resId]['status'] = 'waiting';
                }
            }
        }

        return $args['idsToRetrieve'];
    }

    /**
     * @param array $args
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
     * Create proof from history data, get proof from fast (Fiche de Circulation)
     * @param array $args documentId, config, historyData, filename, signEncodedFile
     * @return array|string[]
     * @throws Exception
     */
    public static function makeHistoryProof(array $args): array
    {
        if (!Validator::stringType()->notEmpty()->validate($args['documentId'])) {
            return ['errors' => 'documentId is not an array'];
        }
        if (!Validator::arrayType()->notEmpty()->validate($args['config'])) {
            return ['errors' => 'config is not an array'];
        }
        if (!Validator::arrayType()->notEmpty()->validate($args['historyData'])) {
            return ['errors' => 'historyData is not an array'];
        }
        if (!Validator::stringType()->notEmpty()->validate($args['filename'])) {
            return ['errors' => 'filename is not a string'];
        }
        if (!Validator::stringType()->notEmpty()->validate($args['signEncodedFile'])) {
            return ['errors' => 'signEncodedFile is not a string'];
        }

        $documentPathToZip = [];
        $tmpPath = CoreConfigModel::getTmpPath();
        $proof = ['history' => $args['historyData']];

        $signDocumentPath = $tmpPath . 'fastSignDoc' . "_" . rand() . '.pdf';
        file_put_contents($signDocumentPath, base64_decode($args['signEncodedFile']));

        $filename = TextFormatModel::formatFilename(['filename' => $args['filename']]);
        if (file_exists($signDocumentPath) && filesize($signDocumentPath) > 0) {
            $proof = [
                'signedDocument' => [
                    'filename'     => $filename,
                    'filenameSize' => filesize($signDocumentPath)
                ]
            ];
            $documentPathToZip[] = ['path' => $signDocumentPath, 'filename' => $filename];
        }

        $fdc = FastParapheurController::getProof(['documentId' => $args['documentId'], 'config' => $args['config']]);
        if (!empty($fdc['errors'])) {
            return ['errors' => $fdc['errors']];
        }
        $fdcPath = $tmpPath . 'ficheDeCirculation' . "_" . rand() . '.pdf';
        file_put_contents($fdcPath, $fdc['response']);

        $documentPathToZip[] = ['path' => $fdcPath, 'filename' => 'ficheDeCirculation.pdf'];
        $proof['proof'] = [
            'filename'     => 'ficheDeCirculation.pdf',
            'filenameSize' => filesize($fdcPath)
        ];
        $proof['history'] = $args['historyData'];

        $proofJson = json_encode($proof, JSON_PRETTY_PRINT);
        $proofJsonPath = $tmpPath . 'maarchProof' . "_" . rand() . '.json';
        $proofCreation = file_put_contents($proofJsonPath, $proofJson);
        if (empty($proofCreation)) {
            return ['errors' => 'Cannot create proof json'];
        }
        $documentPathToZip[] = ['path' => $proofJsonPath, 'filename' => 'maarchProof.json'];

        $zipFileContent = null;
        $zip = new ZipArchive();
        $zipFilename = $tmpPath . 'archivedProof' . '_' . rand() . '.zip';

        if ($zip->open($zipFilename, ZipArchive::CREATE) === true) {
            foreach ($documentPathToZip as $document) {
                if (file_exists($document['path']) && filesize($document['path']) > 0) {
                    $zip->addFile($document['path'], $document['filename']);
                }
            }
            $zip->close();
            $zipFileContent = file_get_contents($zipFilename);
            $documentPathToZip[] = ['path' => $zipFilename];
        } else {
            return ['errors' => 'Cannot create archive zip'];
        }

        foreach ($documentPathToZip as $document) {
            if (file_exists($document['path']) && filesize($document['path']) > 0) {
                unlink($document['path']);
            }
        }

        return ['format' => 'zip', 'encodedProofDocument' => base64_encode($zipFileContent)];
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getProof(array $args): array
    {
        ValidatorModel::notEmpty($args, ['documentId', 'config']);
        ValidatorModel::stringType($args, ['documentId']);
        ValidatorModel::arrayType($args, ['config']);

        $curlReturn = CurlModel::exec([
            'url'          => $args['config']['data']['url'] . '/documents/v2/' . $args['documentId'] . '/getFdc',
            'method'       => 'GET',
            'options'      => [
                CURLOPT_SSLCERT       => $args['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD => $args['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE   => $args['config']['data']['certType']
            ],
            'fileResponse' => true
        ]);

        if ($curlReturn['code'] == 404) {
            return ['code' => 400, 'errors' => "Erreur 404 : {$curlReturn['raw']}"];
        } elseif (!empty($curlReturn['response']['developerMessage'])) {
            return ['code' => 500, 'errors' => $curlReturn['response']['developerMessage']];
        }
        return ['response' => $curlReturn['response']];
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getSignatoryUserInfo(array $args = []): array
    {
        ValidatorModel::notEmpty($args, ['resId', 'config']);

        $signatoryInfo = [];

        if (
            empty($args['config']['data']['integratedWorkflow']) ||
            $args['config']['data']['integratedWorkflow'] == 'false'
        ) {
            $user = DatabaseModel::select([
                'select'    => ['firstname', 'lastname', 'users.id'],
                'table'     => ['listinstance', 'users'],
                'left_join' => ['listinstance.item_id = users.id'],
                'where'     => ['res_id = ?', 'process_date is null', 'difflist_type = ?'],
                'data'      => [$args['resId'], 'VISA_CIRCUIT']
            ]);
            if (!empty($user[0])) {
                $signatoryInfo = $user[0];
            }
        } elseif (!empty($args['valueResponse']['userFastId'] ?? null)) {
            $user = UserModel::get([
                'select' => ['id', "CONCAT(firstname, ' ', lastname) as name"],
                'where'  => ['external_id->>\'fastParapheur\' = ?'],
                'data'   => [$args['valueResponse']['userFastId']]
            ]);
            if (!empty($user[0])) {
                $signatoryInfo = $user[0];
            }
        } elseif (!empty($args['valueResponse']['userFullname'])) {
            $search = $args['valueResponse']['userFullname'];
            $signatoryInfo['name'] = _EXTERNAL_USER . " (" . $search . ")";

            $fpUsers = FastParapheurController::getUsers([
                'config' => [
                    'subscriberId' => $args['config']['data']['subscriberId'],
                    'url'          => $args['config']['data']['url'],
                    'certPath'     => $args['config']['data']['certPath'],
                    'certPass'     => $args['config']['data']['certPass'],
                    'certType'     => $args['config']['data']['certType']
                ]
            ]);
            if (!empty($fpUsers['errors'])) {
                return $signatoryInfo;
            }
            if (empty($fpUsers)) {
                return $signatoryInfo;
            }

            $fpUser = array_filter($fpUsers, function ($fpUser) use ($search) {
                return mb_stripos($fpUser['email'], $search) > -1 ||
                    mb_stripos($fpUser['idToDisplay'], $search) > -1 ||
                    mb_stripos(
                        $fpUser['idToDisplay'],
                        explode(' ', $search)[1] . ' ' . explode(' ', $search)[0]
                    ) >
                    -1;
            });

            if (!empty($fpUser)) {
                $fpUser = array_values($fpUser)[0];

                $alreadyLinkedUsers = UserModel::get([
                    'select' => [
                        'external_id->>\'fastParapheur\' as "fastParapheurEmail"',
                        'trim(concat(firstname, \' \', lastname)) as name'
                    ],
                    'where'  => ['external_id->>\'fastParapheur\' is not null']
                ]);

                foreach ($alreadyLinkedUsers as $alreadyLinkedUser) {
                    if ($fpUser['email'] == $alreadyLinkedUser['fastParapheurEmail']) {
                        $signatoryInfo['name'] = $alreadyLinkedUser['name'] . ' (' .
                            $alreadyLinkedUser['fastParapheurEmail'] . ')';
                        break;
                    }
                }
            }
        }

        return $signatoryInfo;
    }

    /**
     * @param array $args
     * @return void
     * @throws Exception
     */
    public static function processVisaWorkflow(array $args = []): void
    {
        $resIdMaster = $args['res_id_master'] ?? $args['res_id'];

        $attachments = AttachmentModel::get(
            ['select' => ['count(1)'], 'where' => ['res_id_master = ?', 'status = ?'], 'data' => [$resIdMaster, 'FRZ']]
        );
        if ((count($attachments) < 2 && $args['processSignatory']) || !$args['processSignatory']) {
            $visaWorkflow = ListInstanceModel::get([
                'select'  => ['listinstance_id', 'requested_signature'],
                'where'   => ['res_id = ?', 'difflist_type = ?', 'process_date IS NULL'],
                'data'    => [$resIdMaster, 'VISA_CIRCUIT'],
                'orderBY' => ['ORDER BY listinstance_id ASC']
            ]);

            if (!empty($visaWorkflow)) {
                foreach ($visaWorkflow as $listInstance) {
                    if ($listInstance['requested_signature']) {
                        // Stop to the first signatory user
                        if ($args['processSignatory']) {
                            ListInstanceModel::update(
                                [
                                    'set'   => ['signatory' => 'true', 'process_date' => 'CURRENT_TIMESTAMP'],
                                    'where' => ['listinstance_id = ?'],
                                    'data'  => [$listInstance['listinstance_id']]
                                ]
                            );
                        }
                        break;
                    }
                    ListInstanceModel::update(
                        [
                            'set'   => ['process_date' => 'CURRENT_TIMESTAMP'],
                            'where' => ['listinstance_id = ?'],
                            'data'  => [$listInstance['listinstance_id']]
                        ]
                    );
                }
            }
        }
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function upload(array $args): array
    {
        ValidatorModel::notEmpty($args, ['circuitId', 'label', 'businessId']);
        ValidatorModel::stringType($args, ['circuitId', 'label', 'businessId']);

        $circuitId = $args['circuitId'];
        $label = $args['label'];
        $subscriberId = $args['businessId'];

        // Retrieve the annexes of the attachment to sign (other attachment and the original document)
        $annexes = [];
        $annexes['letterbox'] = ResModel::get([
            'select' => [
                'res_id',
                'subject',
                'path',
                'filename',
                'docserver_id',
                'format',
                'category_id',
                'external_id',
                'integrations'
            ],
            'where'  => ['res_id = ?'],
            'data'   => [$args['resIdMaster']]
        ]);

        if (!empty($annexes['letterbox'][0]['docserver_id'])) {
            $adrMainInfo = ConvertPdfController::getConvertedPdfById(
                ['resId' => $args['resIdMaster'], 'collId' => 'letterbox_coll']
            );
            $letterboxPath = DocserverModel::getByDocserverId(
                ['docserverId' => $adrMainInfo['docserver_id'], 'select' => ['path_template']]
            );
            $annexes['letterbox'][0]['filePath'] = $letterboxPath['path_template'] .
                str_replace('#', '/', $adrMainInfo['path']) . $adrMainInfo['filename'];
        }

        $attachments = AttachmentModel::get([
            'select' => [
                'res_id',
                'title',
                'docserver_id',
                'path',
                'filename',
                'format',
                'attachment_type',
                'fingerprint'
            ],
            'where'  => [
                "res_id_master = ?",
                "attachment_type not in (?)",
                "status not in ('DEL', 'OBS', 'FRZ', 'TMP', 'SEND_MASS')",
                "in_signature_book = 'true'"
            ],
            'data'   => [$args['resIdMaster'], ['signed_response']]
        ]);

        $attachmentTypes = AttachmentTypeModel::get(['select' => ['type_id', 'signable']]);
        $attachmentTypes = array_column($attachmentTypes, 'signable', 'type_id');
        foreach ($attachments as $key => $value) {
            if (!$attachmentTypes[$value['attachment_type']]) {
                $annexeAttachmentPath = DocserverModel::getByDocserverId(
                    ['docserverId' => $value['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]
                );
                $value['filePath'] = $annexeAttachmentPath['path_template'] .
                    str_replace('#', DIRECTORY_SEPARATOR, $value['path']) . $value['filename'];

                $docserverType = DocserverTypeModel::getById(
                    ['id' => $annexeAttachmentPath['docserver_type_id'], 'select' => ['fingerprint_mode']]
                );
                $fingerprint = StoreController::getFingerPrint(
                    ['filePath' => $value['filePath'], 'mode' => $docserverType['fingerprint_mode']]
                );
                if ($value['fingerprint'] != $fingerprint) {
                    return ['error' => 'Fingerprints do not match'];
                }

                unset($attachments[$key]);
                $annexes['attachments'][] = $value;
            }
        }
        // END annexes

        $attachmentToFreeze = [];
        foreach ($attachments as $attachment) {
            $resId = $attachment['res_id'];
            $collId = 'attachments_coll';

            $response = FastParapheurController::uploadFile([
                'resId'        => $resId,
                'title'        => $attachment['title'],
                'collId'       => $collId,
                'resIdMaster'  => $args['resIdMaster'],
                'annexes'      => $annexes,
                'circuitId'    => $circuitId,
                'label'        => $label,
                'subscriberId' => $subscriberId,
                'config'       => $args['config']
            ]);

            if (!empty($response['error'])) {
                return $response;
            } else {
                $attachmentToFreeze[$collId][$resId] = $response['success'];
            }
        }

        // Send main document if in signature book
        if (!empty($annexes['letterbox'][0])) {
            $mainDocumentIntegration = json_decode($annexes['letterbox'][0]['integrations'], true);
            $externalId = json_decode($annexes['letterbox'][0]['external_id'], true);
            if ($mainDocumentIntegration['inSignatureBook'] && empty($externalId['signatureBookId'])) {
                $resId = $annexes['letterbox'][0]['res_id'];
                $subject = $annexes['letterbox'][0]['subject'];
                $collId = 'letterbox_coll';
                unset($annexes['letterbox']);

                $response = FastParapheurController::uploadFile([
                    'resId'        => $resId,
                    'title'        => $subject,
                    'collId'       => $collId,
                    'resIdMaster'  => $args['resIdMaster'],
                    'annexes'      => $annexes,
                    'circuitId'    => $circuitId,
                    'label'        => $label,
                    'subscriberId' => $subscriberId,
                    'config'       => $args['config']
                ]);

                if (!empty($response['error'])) {
                    return $response;
                } else {
                    $attachmentToFreeze[$collId][$resId] = $response['success'];
                }
            }
        }

        return ['sended' => $attachmentToFreeze];
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function uploadFile(array $args): array
    {
        $adrInfo = ConvertPdfController::getConvertedPdfById(['resId' => $args['resId'], 'collId' => $args['collId']]);
        if (
            empty($adrInfo['docserver_id']) ||
            strtolower(pathinfo($adrInfo['filename'], PATHINFO_EXTENSION)) != 'pdf'
        ) {
            return ['error' => 'Document ' . $args['resIdMaster'] . ' is not converted in pdf'];
        }
        $attachmentPath = DocserverModel::getByDocserverId(
            ['docserverId' => $adrInfo['docserver_id'], 'select' => ['path_template']]
        );
        $attachmentFilePath = $attachmentPath['path_template'] . str_replace('#', '/', $adrInfo['path']) .
            $adrInfo['filename'];
        $title = TextFormatModel::formatFilename(['filename' => str_replace(' ', '_', $args['title'])]);
        $attachmentFileName = $title . '_' . $args['resIdMaster'] . '_' . rand(0001, 9999) . '.pdf';

        $zip = new ZipArchive();
        $tmpPath = CoreConfigModel::getTmpPath();
        $zipFilePath = $tmpPath . DIRECTORY_SEPARATOR
            . $attachmentFileName .
            '.zip';  // The zip file need to have the same name as the attachment we want to sign

        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
            return ['error' => "Can not open file : <$zipFilePath>\n"];
        }
        $zip->addFile($attachmentFilePath, $attachmentFileName);

        if (!empty($args['annexes']['letterbox'][0]['filePath'])) {
                $zip->addFile(
                    $args['annexes']['letterbox'][0]['filePath'],
                    'document_principal.' . $args['annexes']['letterbox'][0]['format']
                ) ?? null;
        }

        if (isset($args['annexes']['attachments'])) {
            for ($j = 0; $j < count($args['annexes']['attachments']); $j++) {
                $zip->addFile(
                    $args['annexes']['attachments'][$j]['filePath'],
                    'PJ_' . ($j + 1) . '.' . $args['annexes']['attachments'][$j]['format']
                );
            }
        }

        $zip->close();

        $result = FastParapheurController::uploadFileToFast([
            'config'        => $args['config'],
            'circuitId'     => str_replace('.', '-', $args['circuitId']),
            'fileName'      => $attachmentFileName . '.zip',
            'b64Attachment' => file_get_contents($zipFilePath),
            'label'         => $args['label']
        ]);
        if (!empty($result['error'])) {
            return ['error' => $result['error'], 'code' => $result['code']];
        }

        FastParapheurController::processVisaWorkflow(
            ['res_id_master' => $args['resIdMaster'], 'processSignatory' => false]
        );
        $documentId = $result['response'];
        return ['success' => (string)$documentId];
    }

    /**
     * Function to send files to FastParapheur only
     * @param array $args :
     *                      - config
     *                      - circuitId
     *                      - fileName
     *                      - circuitB64AttachmentId
     *                      - label
     * @throws Exception
     */
    public static function uploadFileToFast(array $args): array
    {
        ValidatorModel::notEmpty($args, ['config', 'circuitId']);
        ValidatorModel::arrayType($args, ['config']);
        ValidatorModel::stringType($args, ['circuitId']);

        $curlReturn = CurlModel::exec([
            'url'           => $args['config']['data']['url'] . '/documents/v2/' .
                $args['config']['data']['subscriberId'] . '/' .
                $args['circuitId'] . '/upload',
            'method'        => 'POST',
            'options'       => [
                CURLOPT_SSLCERT       => $args['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD => $args['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE   => $args['config']['data']['certType']
            ],
            'multipartBody' => [
                'content' => ['isFile' => true, 'filename' => $args['fileName'], 'content' => $args['b64Attachment']],
                'label'   => $args['label'],
                'comment' => ""
            ]
        ]);

        if ($curlReturn['code'] == 404) {
            return ['error' => 'Erreur 404 : ' . $curlReturn['raw'], 'code' => $curlReturn['code']];
        } elseif (!empty($curlReturn['errors'])) {
            return ['error' => $curlReturn['errors'], 'code' => $curlReturn['code']];
        } elseif (!empty($curlReturn['response']['developerMessage'])) {
            return ['error' => $curlReturn['response']['developerMessage'], 'code' => $curlReturn['code']];
        }

        return ['response' => $curlReturn['response']];
    }

    /**
     * upload to FastParapheur with integrated workflow steps
     *
     * @param array $args :
     *   - resIdMaster: identifier of the res_letterbox item to send
     *   - config: FastParapheur configuration
     *   - steps: an array of steps, each being an associative array with:
     *     - mode: 'visa' or 'signature'
     *     - type: 'maarchCourrierUserId' or 'fastParapheurUserEmail'
     *     - id: identifies the user, int for maarchCourrierUserId, string for fastParapheurUserEmail
     *
     * @return array|bool links between MC and FP identifiers:
     *   [
     *     'sended' => [
     *       'letterbox_coll' => [
     *         $maarchCourrierResId => $fastParapheurDocumentId,
     *         ...
     *       ],
     *       'attachments_coll' => [
     *         $maarchCourrierAttachmentResId => $fastParapheurDocumentId,
     *         ...
     *       ]
     *     ]
     *   ]
     * @throws Exception
     */
    public static function uploadWithSteps(array $args): array|bool
    {
        ValidatorModel::notEmpty($args, ['resIdMaster', 'steps', 'config', 'workflowType']);
        ValidatorModel::intType($args, ['resIdMaster']);
        ValidatorModel::arrayType($args, ['steps', 'config', 'stampsSteps']);
        ValidatorModel::stringType($args, ['workflowType']);

        $subscriberId = $args['config']['subscriberId'] ?? null;
        if (empty($subscriberId)) {
            return ['error' => _NO_SUBSCRIBER_ID_FOUND_FAST_PARAPHEUR];
        }
        if (empty($args['workflowType'])) {
            return ['error' => _NO_WORKFLOW_TYPE_FOUND_FAST_PARAPHEUR];
        }

        $signatureModes = FastParapheurController::getSignatureModes(['mapping' => false]);
        if (!empty($signatureModes['errors'])) {
            return ['errors' => $signatureModes['errors']];
        }

        $signatureModes = array_column($signatureModes['signatureModes'], 'id');

        $circuit = [
            'type'  => $args['workflowType'],
            'steps' => []
        ];

        $otpInfo = [];
        $indexOTP = 0;
        foreach ($args['steps'] as $index => $step) {
            $stepMode = FastParapheurController::getSignatureModeById(['signatureModeId' => $step['mode']]);

            if (in_array($stepMode, $signatureModes) && !empty($step['type']) && !empty($step['id'])) {
                if ($step['type'] == 'maarchCourrierUserId') {
                    $user = UserModel::getById(
                        ['id' => $step['id'], 'select' => ['external_id->>\'fastParapheur\' as "fastParapheurEmail"']]
                    );
                    if (empty($user['fastParapheurEmail'])) {
                        return ['errors' => 'no FastParapheurEmail for user ' . $step['id'], 'code' => 400];
                    }
                    $circuit['steps'][] = [
                        'step'    => $stepMode,
                        'members' => [$user['fastParapheurEmail']]
                    ];
                } elseif ($step['type'] == 'fastParapheurUserEmail') {
                    $circuit['steps'][] = [
                        'step'    => $stepMode,
                        'members' => [$step['id']]
                    ];
                }
            } elseif (
                $step['type'] == 'externalOTP'
                && Validator::notEmpty()->phone()->validate($step['phone'])
                && Validator::notEmpty()->email()->validate($step['email'])
                && Validator::notEmpty()->stringType()->validate($step['firstname'])
                && Validator::notEmpty()->stringType()->validate($step['lastname'])
            ) {
                $circuit['steps'][] = [
                    'step'    => 'OTPSignature',
                    'members' => [$step['email']]
                ];
                $otpInfo['OTP_firstname_' . $indexOTP] = $step['firstname'];
                $otpInfo['OTP_lastname_' . $indexOTP] = $step['lastname'];
                $otpInfo['OTP_phonenumber_' . $indexOTP] = $step['phone'];
                $otpInfo['OTP_email_' . $indexOTP] = $step['email'];
                $indexOTP++;
            } else {
                return ['error' => 'step number ' . ($index + 1) . ' is invalid', 'code' => 400];
            }
        }
        if (empty($circuit['steps'])) {
            return ['error' => 'steps are empty or invalid', 'code' => 400];
        }

        $otpInfoXML = null;
        if (!empty($otpInfo)) {
            $otpInfoXML = FastParapheurController::generateOtpXml([
                'prettyPrint' => true,
                'otpInfo'     => $otpInfo
            ]);
            if (!empty($otpInfoXML['errors'])) {
                return ['error' => $otpInfoXML['errors']];
            }
        }

        $resource = ResModel::getById([
            'resId'  => $args['resIdMaster'],
            'select' => [
                'res_id',
                'subject',
                'typist',
                'integrations',
                'docserver_id',
                'path',
                'filename',
                'category_id',
                'format',
                'external_id',
                'external_state'
            ]
        ]);
        if (empty($resource)) {
            return ['error' => 'resource does not exist', 'code' => 400];
        }
        $resource['external_id'] = json_decode($resource['external_id'], true);

        if ($resource['format'] != 'pdf' && !empty($resource['docserver_id'])) {
            $convertedDocument = ConvertPdfController::getConvertedPdfById([
                'collId' => 'letterbox_coll',
                'resId'  => $args['resIdMaster']
            ]);
            if (!empty($convertedDocument['errors'])) {
                return ['error' => 'unable to convert main document'];
            }
            $resource['docserver_id'] = $convertedDocument['docserver_id'];
            $resource['path'] = $convertedDocument['path'];
            $resource['filename'] = $convertedDocument['filename'];
        }

        $sentAttachments = [];
        $sentMainDocument = [];
        $docservers = DocserverModel::get(['select' => ['docserver_id', 'path_template']]);
        $docservers = array_column($docservers, 'path_template', 'docserver_id');
        $attachmentTypeSignable = AttachmentTypeModel::get(['select' => ['type_id', 'signable']]);
        $attachmentTypeSignable = array_column($attachmentTypeSignable, 'signable', 'type_id');

        if (!empty($docservers[$resource['docserver_id']])) {
            $mainDocumentSigned = AdrModel::getConvertedDocumentById([
                'select' => [1],
                'resId'  => $args['resIdMaster'],
                'collId' => 'letterbox_coll',
                'type'   => 'SIGN'
            ]);

            $sentMainDocument = [
                'resId'        => $resource['res_id'],
                'subject'      => $resource['subject'],
                'signable'     => empty($mainDocumentSigned),
                'integrations' => $resource['integrations'],
                'filePath'     => $docservers[$resource['docserver_id']] . $resource['path'] . $resource['filename']
            ];
        }

        $attachments = AttachmentModel::get([
            'select' => [
                'res_id',
                'title',
                'docserver_id',
                'path',
                'filename',
                'format',
                'attachment_type',
                'fingerprint',
                'external_state'
            ],
            'where'  => [
                'res_id_master = ?',
                'attachment_type not in (?)',
                'status not in (\'DEL\', \'OBS\', \'FRZ\', \'TMP\', \'SEND_MASS\')',
                'in_signature_book is true'
            ],
            'data'   => [$args['resIdMaster'], AttachmentTypeController::UNLISTED_ATTACHMENT_TYPES]
        ]);

        #region Prepare the appendices foreach document to be signed
        $appendices = [];
        foreach ($attachments as $key => $value) {
            if (!$attachmentTypeSignable[$value['attachment_type']]) {
                $annexeAttachmentPath = DocserverModel::getByDocserverId([
                    'docserverId' => $value['docserver_id'],
                    'select'      => ['path_template', 'docserver_type_id']
                ]);
                $filePath = $annexeAttachmentPath['path_template'] .
                    str_replace('#', DIRECTORY_SEPARATOR, $value['path']) . $value['filename'];

                $docserverType = DocserverTypeModel::getById([
                    'id'     => $annexeAttachmentPath['docserver_type_id'],
                    'select' => ['fingerprint_mode']
                ]);
                $fingerprint = StoreController::getFingerPrint([
                    'filePath' => $filePath,
                    'mode'     => $docserverType['fingerprint_mode']
                ]);
                if ($value['fingerprint'] != $fingerprint) {
                    return ['error' => 'Fingerprints do not match'];
                }

                $appendices[] = [
                    'isFile'   => true,
                    'content'  => file_get_contents($filePath),
                    'filename' => TextFormatModel::formatFilename([
                            'filename'  => $value['title'],
                            'maxLength' => 251
                        ]) . '.' . pathinfo($filePath, PATHINFO_EXTENSION)
                ];

                // remove non signable attachment from list
                unset($attachments[$key]);
            }
        }
        #endregion

        #region Prepare signable attachment list
        foreach ($attachments as $attachment) {
            if ($attachment['format'] != 'pdf') {
                $convertedAttachment = ConvertPdfController::getConvertedPdfById([
                    'collId' => 'attachments_coll',
                    'resId'  => $attachment['res_id']
                ]);
                if (!empty($convertedAttachment['errors'])) {
                    continue;
                }
                $attachment['docserver_id'] = $convertedAttachment['docserver_id'];
                $attachment['path'] = $convertedAttachment['path'];
                $attachment['filename'] = $convertedAttachment['filename'];
                $attachment['format'] = 'pdf';
            }
            $sentAttachments[] = [
                'resId'    => $attachment['res_id'],
                'title'    => $attachment['title'],
                'filePath' => $docservers[$attachment['docserver_id']] . $attachment['path'] .
                    $attachment['filename']
            ];
        }
        #endregion

        $user = UserModel::getById(['id' => $GLOBALS['id'], 'select' => ['user_id']]);
        $summarySheetFilePath = FastParapheurController::getSummarySheetFile([
            'docResId' => $args['resIdMaster'],
            'login'    => $user['user_id']
        ]);

        $documentsToSign = FastParapheurController::prepareDocumentsToSign(
            $args['steps'],
            $args['stampsSteps'],
            $sentMainDocument,
            $summarySheetFilePath,
            $sentAttachments,
            $appendices,
            isset($otpInfoXML['content'])
        );

        if (empty($documentsToSign)) {
            return ['error' => 'resource has nothing to sign', 'code' => 400];
        }

        $returnIds = ['sended' => ['letterbox_coll' => [], 'attachments_coll' => []]];

        foreach ($documentsToSign as $docToSign) {
            $result = FastParapheurController::onDemandUploadFilesToFast([
                'config'   => $args['config'],
                'document' => $docToSign,
                'circuit'  => $circuit,
            ]);
            if (!empty($result['error'])) {
                return ['code' => $result['code'], 'error' => $result['error']];
            }

            if (!empty($otpInfoXML['content'])) {
                $curlReturn = CurlModel::exec([
                    'method'        => 'PUT',
                    'url'           => $args['config']['url'] . '/documents/v2/otp/' . $result['response'] .
                        '/metadata/define',
                    'options'       => [
                        CURLOPT_SSLCERT       => $args['config']['certPath'],
                        CURLOPT_SSLCERTPASSWD => $args['config']['certPass'],
                        CURLOPT_SSLCERTTYPE   => $args['config']['certType']
                    ],
                    'multipartBody' => [
                        'otpinformation' => [
                            'isFile'   => true,
                            'filename' => 'METAS_API.xml',
                            'content'  => $otpInfoXML['content']
                        ]
                    ]
                ]);
                if ($curlReturn['code'] != 200) {
                    return ['error' => $curlReturn, 'code' => $curlReturn['code']];
                }
            }

            $returnIds['sended'][$docToSign['id']['collId']][$docToSign['id']['resId']] = (string)$result['response'];
        }

        return $returnIds;
    }

    /**
     * Prepare an array of signable documents for {@see FastParapheurController::onDemandUploadFilesToFast}
     *
     * @param array $workflowSteps
     * @param array $stampsSteps
     * @param array $mainResource
     * @param string $summarySheetFilePath
     * @param array $attachments
     * @param array $appendices
     * @param bool $isOtpActive
     * @param string $comment
     *
     * @return array of signable document item :
     *  - id:
     *      - collId: Document type, letterbox_coll or attachments_coll
     *      - resId: Document id
     *  - doc:
     *       - path: Document path
     *       - filename: Document file path
     * - appendices: List of non signable documents that are integrated
     * - comment: Annotation from user
     *
     * @throws Exception
     */
    public static function prepareDocumentsToSign(
        array $workflowSteps,
        array $stampsSteps,
        array $mainResource,
        string $summarySheetFilePath,
        array $attachments = [],
        array $appendices = [],
        bool $isOtpActive = false,
        string $comment = ""
    ): array {
        ValidatorModel::notEmpty($mainResource, ['resId', 'subject', 'filePath', 'integrations']);
        ValidatorModel::intType($mainResource, ['resId']);
        ValidatorModel::stringType($mainResource, ['subject', 'filePath', 'integrations']);
        ValidatorModel::boolType($mainResource, ['signable']);

        $doc = [];

        if (!$isOtpActive) {
            if (!empty($summarySheetFilePath)) {
                $appendices[] = [
                    'isFile'   => true,
                    'content'  => file_get_contents($summarySheetFilePath),
                    'filename' => TextFormatModel::formatFilename([
                        'filename'  => 'Fiche-De-Liaison.' . pathinfo($summarySheetFilePath, PATHINFO_EXTENSION),
                        'maxLength' => 50
                    ])
                ];
                unlink($summarySheetFilePath);
            }

            $appendices[] = [
                'isFile'   => true,
                'content'  => file_get_contents($mainResource['filePath']),
                'filename' => TextFormatModel::formatFilename([
                        'filename'  => $mainResource['subject'],
                        'maxLength' => 251
                    ]) . '.' . pathinfo($mainResource['filePath'], PATHINFO_EXTENSION)
            ];
        }

        foreach ($attachments as $attachment) {
            if (
                !isset($attachment['resId']) ||
                !isset($attachment['title']) ||
                !isset($attachment['filePath'])
            ) {
                continue;
            }
            if (!is_file($attachment['filePath'])) {
                continue;
            }
            $doc[] = [
                'id'         => [
                    'collId' => 'attachments_coll',
                    'resId'  => $attachment['resId']
                ],
                'doc'        => [
                    'path'     => $attachment['filePath'],
                    'filename' => TextFormatModel::formatFilename([
                            'filename'  => $attachment['title'],
                            'maxLength' => 251
                        ]) . '.' . pathinfo($attachment['filePath'], PATHINFO_EXTENSION)
                ],
                'appendices' => $appendices,
                'pictograms' => FastParapheurController::generateXmlPictogramme(
                    FastParapheurController::convertCoordinatesToMillimeter(
                        $attachment['filePath'],
                        $stampsSteps[$attachment['resId']] ?? []
                    )
                ),
                'comment'    => $comment
            ];

            $externalState = json_decode($attachment['external_state'] ?? '{}', true);
            $externalState['signatureBookWorkflow']['workflow'] = $workflowSteps;
            AttachmentModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$attachment['resId']],
                'postSet' => [
                    'external_state' => 'jsonb_set(external_state, \'{signatureBookWorkflow}\', \'' .
                        json_encode($externalState['signatureBookWorkflow']) . '\'::jsonb)'
                ]
            ]);
        }

        // Send main document if in signature book
        $mainResource['integrations'] = json_decode($mainResource['integrations'], true);

        if (
            !empty($mainResource['integrations']['inSignatureBook']) &&
            !empty($mainResource['filePath']) &&
            !empty($mainResource['signable'])
        ) {
            // The last appendix is always the main document
            unset($appendices[count($appendices) - 1]);

            $doc[] = [
                'id'         => [
                    'collId' => 'letterbox_coll',
                    'resId'  => $mainResource['resId']
                ],
                'doc'        => [
                    'path'     => $mainResource['filePath'],
                    'filename' => TextFormatModel::formatFilename([
                            'filename'  => $mainResource['subject'],
                            'maxLength' => 251
                        ]) . '.' . pathinfo($mainResource['filePath'], PATHINFO_EXTENSION)
                ],
                'appendices' => $appendices,
                'pictograms' => FastParapheurController::generateXmlPictogramme(
                    FastParapheurController::convertCoordinatesToMillimeter(
                        $mainResource['filePath'],
                        $stampsSteps[$mainResource['resId']] ?? []
                    )
                ),
                'comment'    => $comment
            ];

            $externalState = json_decode($resource['external_state'] ?? '{}', true);
            $externalState['signatureBookWorkflow']['workflow'] = $workflowSteps;
            ResModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$mainResource['resId']],
                'postSet' => [
                    'external_state' => 'jsonb_set(external_state, \'{signatureBookWorkflow}\', \'' .
                        json_encode($externalState['signatureBookWorkflow']) . '\'::jsonb)'
                ]
            ]);
        }

        return $doc;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function onDemandUploadFilesToFast(array $args): array
    {
        ValidatorModel::notEmpty($args, ['config', 'document', 'circuit']);
        ValidatorModel::arrayType($args, ['config', 'document', 'circuit']);

        $multipartBody = [
            'comment' => $args['document']['comment'],
            'doc'     => [
                'isFile'   => true,
                'filename' => $args['document']['doc']['filename'],
                'content'  => file_get_contents($args['document']['doc']['path'])
            ],
            'annexes' => ['subvalues' => $args['document']['appendices']],
            'circuit' => json_encode($args['circuit'], true)
        ];

        if (!empty($args['document']['pictograms'] ?? null)) {
            $multipartBody['pictograms'] = [
                'isFile'   => true,
                'filename' => 'pictograms.xml',
                'content'  => $args['document']['pictograms']
            ];
        }

        $curlReturn = CurlModel::exec([
            'method'        => 'POST',
            'url'           => $args['config']['url'] . '/documents/ondemand/' . $args['config']['subscriberId'] .
                '/upload',
            'options'       => [
                CURLOPT_SSLCERT       => $args['config']['certPath'],
                CURLOPT_SSLCERTPASSWD => $args['config']['certPass'],
                CURLOPT_SSLCERTTYPE   => $args['config']['certType']
            ],
            'multipartBody' => $multipartBody
        ]);

        if ($curlReturn['code'] != 200) {
            return ['code' => $curlReturn['code'], 'error' => $curlReturn['errors']];
        }
        if (!empty($curlReturn['response']['developerMessage'])) {
            return ['code' => $curlReturn['code'], 'error' => $curlReturn['response']['userFriendlyMessage']];
        }

        return ['response' => $curlReturn['response']];
    }

    /**
     * @param array $args
     * @return array|false
     * @throws Exception
     */
    public static function download(array $args): bool|array
    {
        $curlReturn = CurlModel::exec([
            'url'          => $args['config']['data']['url'] . '/documents/v2/' . $args['documentId'] . '/download',
            'method'       => 'GET',
            'options'      => [
                CURLOPT_SSLCERT       => $args['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD => $args['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE   => $args['config']['data']['certType'],
            ],
            'fileResponse' => true
        ]);

        if ($curlReturn['code'] == 404) {
            echo "Erreur 404 : {$curlReturn['raw']}";
            return false;
        } elseif (!empty($curlReturn['response']['developerMessage'])) {
            echo $curlReturn['response']['developerMessage'];
            return false;
        } else {
            return ['b64FileContent' => base64_encode($curlReturn['response'])];
        }
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function sendDatas(array $args): array
    {
        $config = $args['config'];

        if (!empty($config['data']['integratedWorkflow']) && $config['data']['integratedWorkflow'] == 'true') {
            $steps = FastParapheurController::prepareSteps($args['steps']);
            if (isset($steps['error'])) {
                return $steps;
            }

            $stampsSteps = FastParapheurController::prepareStampsSteps($args['steps']);

            return FastParapheurController::uploadWithSteps([
                'config'       => $config['data'],
                'resIdMaster'  => $args['resIdMaster'],
                'steps'        => $steps,
                'stampsSteps'  => $stampsSteps,
                'workflowType' => $args['workflowType']
            ]);
        } else {
            // We need the SIRET field and the user_id of the signatory user's primary entity
            $signatory = DatabaseModel::select([
                'select'    => ['user_id', 'external_id', 'entities.entity_label'],
                'table'     => ['listinstance', 'users_entities', 'entities'],
                'left_join' => ['item_id = user_id', 'users_entities.entity_id = entities.entity_id'],
                'where'     => ['res_id = ?', 'item_mode = ?', 'process_date is null'],
                'data'      => [$args['resIdMaster'], 'sign']
            ])[0] ?? null;
            $redactor = DatabaseModel::select([
                'select'    => ['short_label'],
                'table'     => ['res_view_letterbox', 'users_entities', 'entities'],
                'left_join' => ['dest_user = user_id', 'users_entities.entity_id = entities.entity_id'],
                'where'     => ['res_id = ?'],
                'data'      => [$args['resIdMaster']]
            ])[0] ?? null;

            $signatory['business_id'] = json_decode(
                $signatory['external_id'] ?? null,
                true
            )['fastParapheurSubscriberId'] ?? null;
            if (empty($signatory['business_id']) || str_starts_with($signatory['business_id'], 'org')) {
                $signatory['business_id'] = $config['data']['subscriberId'];
            }

            $user = [];
            if (!empty($signatory['user_id'])) {
                $user = UserModel::getById(['id' => $signatory['user_id'], 'select' => ['user_id']]);
            }

            if (empty($user['user_id'])) {
                return ['error' => _VISA_WORKFLOW_NOT_FOUND];
            }

            // check if circuitId is an email
            if (Validator::email()->notEmpty()->validate($user['user_id'])) {
                $user['user_id'] = explode("@", $user['user_id'])[0];
            }

            if (empty($signatory['business_id'])) {
                return ['error' => _NO_BUSINESS_ID];
            }

            if (empty($redactor['short_label'])) {
                return ['error' => _VISA_WORKFLOW_ENTITY_NOT_FOUND];
            }

            return FastParapheurController::upload([
                'config'      => $config,
                'resIdMaster' => $args['resIdMaster'],
                'businessId'  => $signatory['business_id'],
                'circuitId'   => $user['user_id'],
                'label'       => $redactor['short_label']
            ]);
        }
    }

    /**
     * Prepare the workflow steps for each user
     *
     * @param array $steps An array containing steps data
     *
     * @return array
     * @throws Exception
     */
    public static function prepareSteps(array $steps): array
    {
        $mappedSteps = [];
        if (empty($steps)) {
            return ['error' => 'steps is empty'];
        }
        $resId = $steps[0]['resId'] ?? null;
        if ($resId === null) {
            return ['error' => 'no resId found in steps'];
        }

        $areWeUsingOTP = false;
        foreach ($steps as $step) {
            if ($step['resId'] !== $resId) {
                continue;
            }
            if (!empty($step['externalInformations'])) {
                $mappedSteps[$step['sequence']] = [
                    'mode'      => $step['signatureMode'],
                    'type'      => 'externalOTP',
                    'phone'     => $step['externalInformations']['phone'] ?? null,
                    'email'     => $step['externalInformations']['email'] ?? null,
                    'firstname' => $step['externalInformations']['firstname'] ?? null,
                    'lastname'  => $step['externalInformations']['lastname'] ?? null
                ];
                $areWeUsingOTP = true;
            } else {
                $mappedSteps[$step['sequence']] = [
                    'mode' => $step['signatureMode'],
                    'type' => 'fastParapheurUserEmail',
                    'id'   => $step['externalId']
                ];
            }
        }

        $optionOTP = FastParapheurController::isOtpActive();
        if (!empty($optionOTP['errors'])) {
            return $optionOTP['errors'];
        } elseif (!$optionOTP['OTP'] && $areWeUsingOTP) {
            return ['error' => _EXTERNAL_USER_FOUND_BUT_OPTION_OTP_DISABLE];
        }
        return $mappedSteps;
    }

    /**
     * Prepare the stamp steps for each document to be signed, if there are signature positions.
     *
     * @param array $steps An array containing steps data.
     *                     Each element should represent a step with signature positions information.
     * @return array The prepared stamps positions array.
     */
    public static function prepareStampsSteps(array $steps): array
    {
        $stampsPositions = [];

        foreach ($steps as $step) {
            if (
                isset($step['signaturePositions'][0]['page']) &&
                isset($step['signaturePositions'][0]['positionX']) &&
                isset($step['signaturePositions'][0]['positionY'])
            ) {
                $type = 'pictogramme-visa';
                $role = 'VALIDEUR';
                $validActionByRole = "Validé par: \${{$role}}";
                $roleDate = 'DATE_VISA';

                if ($step['action'] === 'sign') {
                    $type = 'pictogramme-signature';
                    $role = 'SIGNATAIRE';
                    $validActionByRole = "Signé par: \${{$role}}";
                    $roleDate = 'DATE_SIGNATURE';

                    if (!empty($step['externalInformations'])) {
                        $validActionByRole = "Signé par: \${OTP_INFOS[firstname,lastname]}";
                    }
                }

                $stampsPositions[$step['resId']][$type][$step['sequence']] = [
                    'index'     => ($step['sequence'] + 1), // the step to which the pictogram will be associated
                    'border'    => 'true',
                    'opacite'   => 'true',
                    'font-size' => '6',
                    // Position allows you to define the position of the pictogram in the document. Sizes are in mm.
                    // The 0.0 point corresponds to the bottom left corner of the document.
                    'position'  => [
                        'height' => FastParapheurController::PICTOGRAM_DIMENSIONS['height'],
                        'width'  => FastParapheurController::PICTOGRAM_DIMENSIONS['width'],
                        'page'   => $step['signaturePositions'][0]['page'], // $ corresponds the last page
                        // coordinates of the pictogram frame corresponding to the bottom left corner
                        'x'      => $step['signaturePositions'][0]['positionX'],
                        'y'      => $step['signaturePositions'][0]['positionY']
                    ],
                    // The center box is reserved for the signature image. Possible values are ABONNE, CIRCUIT,
                    // VALIDEUR and SIGNATAIRE.
                    'center'    => [
                        ['name' => 'IMAGE', 'value' => $role]
                    ],
                    // top, bottom, right and left correspond to the boxes around the image.
                    'bottom'    => [
                        ['name' => 'CHAMP_LIBRE', 'value' => $validActionByRole],
                        ['name' => $roleDate]
                    ]
                ];
            }
        }

        return $stampsPositions;
    }

    /**
     * Convert percentage-based coordinates to millimeters within a PDF file.
     *
     * @param string $filePath The path to the PDF file.
     * @param array $stampPositionsArray An array containing stamp positions data.
     *                                   Each element should be an array of stamp positions.
     *                                   Each stamp position should be an array containing position data.
     *
     * @return array The updated stamp positions array with coordinates converted to millimeters.
     * @throws Exception From FPDI library if it can't load pdf file or can't find the page.
     */
    public static function convertCoordinatesToMillimeter(string $filePath, array $stampPositionsArray): array
    {
        $libPath = CoreConfigModel::getFpdiPdfParserLibrary();
        if (file_exists($libPath)) {
            require_once $libPath;
        }

        $pdf = new Fpdi('');  // orientation param is empty for automatic orientation
        $pdf->setSourceFile($filePath);

        foreach ($stampPositionsArray as &$stampPosition) {
            foreach ($stampPosition as &$position) {
                $pageId = $pdf->importPage($position['position']['page']);
                $dimensions = $pdf->getTemplateSize($pageId);

                // Convert coordinates to millimeters
                $position['position']['x'] = (int)($dimensions['width'] * $position['position']['x'] / 100);
                $position['position']['y'] = (int)($dimensions['height'] -
                    ($dimensions['height'] * $position['position']['y'] / 100) -
                    FastParapheurController::PICTOGRAM_DIMENSIONS['height']);
            }
        }

        return $stampPositionsArray;
    }

    /**
     * Recommandations minimales Fast à respecter
     * Espacement minimum de 30 minutes pour le même document
     *
     * @param   $args :
     *  - documentId : 'externalId' of res_letterbox
     *  - config : FastParapheur configuration
     *
     * @throws Exception
     */
    public static function getDocumentHistory(array $args): array
    {
        if (!Validator::notEmpty()->validate($args['documentId'])) {
            return ['errors' => 'documentId not found'];
        }
        if (!Validator::arrayType()->notEmpty()->validate($args['config'])) {
            return ['errors' => 'config is not an array'];
        }

        $curlReturn = CurlModel::exec([
            'url'     => $args['config']['data']['url'] . '/documents/v2/' . $args['documentId'] . '/history',
            'method'  => 'GET',
            'options' => [
                CURLOPT_SSLCERT       => $args['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD => $args['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE   => $args['config']['data']['certType']
            ]
        ]);

        if ($curlReturn['code'] == 404) {
            return ['code' => $curlReturn['code'], 'errors' => $curlReturn['raw']];
        } elseif (!empty($curlReturn['response']['developerMessage'])) {
            return ['code' => $curlReturn['code'], 'errors' => $curlReturn['response']['developerMessage']];
        }

        // Simuler une erreur dans la récupération de l'historique de document FAST
        // return ['code' => $curlReturn['code'], 'errors' => 'Fake error in Fast history'];

        return ['response' => $curlReturn['response']];
    }

    /**
     * @param array $args
     * @return array|true
     * @throws Exception
     */
    public static function updateFetchHistoryDateByExternalId(array $args): array|bool
    {
        $tag = "[updateFetchHistoryDateByExternalId] ";
        if (!Validator::stringType()->notEmpty()->validate($args['type'])) {
            return ['errors' => $tag . 'type is not a string'];
        }
        if (!Validator::notEmpty()->validate($args['resId'])) {
            return ['errors' => $tag . 'resId is not found'];
        }

        if (!empty($args['type']) && $args['type'] == 'resource') {
            $resource = ResModel::get([
                'select' => ['res_id', 'external_id', 'external_state'],
                'where'  => ["res_id = ?"],
                'data'   => [$args['resId']]
            ]);
            if (empty($resource)) {
                return ['errors' => $tag . 'Resource (' . $args['resId'] . ') does not exist'];
            }
        } else {
            $resource = AttachmentModel::get([
                'select' => ['res_id', 'res_id_master', 'external_id', 'external_state'],
                'where'  => ["res_id = ?"],
                'data'   => [$args['resId']]
            ]);
            if (empty($resource)) {
                return ['errors' => $tag . 'Attachment (' . $args['resId'] . ') does not exist'];
            }
        }

        $resource = $resource[0];

        $externalId = json_decode($resource['external_id'], true);
        if (empty($externalId['signatureBookId'])) {
            return ['errors' => $tag . 'Resource is not linked to Fast Parapheur'];
        }

        $externalState = json_decode($resource['external_state'], true);

        $currentDate = new DateTimeImmutable();
        $externalState['signatureBookWorkflow']['fetchDate'] = $currentDate->format('c');
        if (!empty($args['type']) && $args['type'] == 'resource') {
            ResModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$resource['res_id']],
                'postSet' => [
                    'external_state' => 'jsonb_set(external_state, \'{signatureBookWorkflow}\', \'' .
                        json_encode($externalState['signatureBookWorkflow']) . '\'::jsonb)'
                ]
            ]);
        } else {
            AttachmentModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$resource['res_id']],
                'postSet' => [
                    'external_state' => 'jsonb_set(external_state, \'{signatureBookWorkflow}\', \'' .
                        json_encode($externalState['signatureBookWorkflow']) . '\'::jsonb)'
                ]
            ]);
        }

        return true;
    }

    /**
     * @param array $args
     * @return string
     * @throws Exception
     */
    public static function getRefusalMessage(array $args): string
    {
        $curlReturn = CurlModel::exec([
            'url'     => $args['config']['data']['url'] . '/documents/v2/' . $args['documentId'] . '/comments/refusal',
            'method'  => 'GET',
            'options' => [
                CURLOPT_SSLCERT       => $args['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD => $args['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE   => $args['config']['data']['certType']
            ]
        ]);

        $response = "";
        if (!empty($curlReturn['response']['developerMessage']) && $args['version'] == 'noVersion') {
            $attachmentName = AttachmentModel::getById(['select' => ['title'], 'id' => $args['res_id']]);
            $str = explode(':', $curlReturn['response']['developerMessage']);
            unset($str[0]);
            $response = _FOR_ATTACHMENT . " \"{$attachmentName['title']}\". " . implode('.', $str);
        } elseif (!empty($curlReturn['response']['developerMessage'])) {
            $str = explode(':', $curlReturn['response']['developerMessage']);
            unset($str[0]);
            $response = _FOR_MAIN_DOC . ". " . implode('.', $str);
        } elseif (!empty($curlReturn['response']['comment']) && $args['version'] == 'noVersion') {
            $attachmentName = AttachmentModel::getById(['select' => ['title'], 'id' => $args['res_id']]);
            $response = _FOR_ATTACHMENT . " \"{$attachmentName['title']}\". " . $curlReturn['response']['comment'];
        } elseif (!empty($curlReturn['response']['comment'])) {
            $response = _FOR_MAIN_DOC . ". " . $curlReturn['response']['comment'];
        }
        return $response;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getUsers(array $args): array
    {
        $subscriberId = $args['subscriberId'] ?? $args['config']['subscriberId'] ?? null;
        if (empty($subscriberId)) {
            return ['code' => 400, 'errors' => 'no subscriber id provided'];
        }
        $curlReturn = CurlModel::exec([
            'url'     => $args['config']['url'] . '/exportUsersData?siren=' . urlencode($subscriberId),
            'method'  => 'GET',
            'options' => [
                CURLOPT_SSLCERT       => $args['config']['certPath'],
                CURLOPT_SSLCERTPASSWD => $args['config']['certPass'],
                CURLOPT_SSLCERTTYPE   => $args['config']['certType']
            ]
        ]);
        if (!empty($curlReturn['errors'])) {
            return ['code' => $curlReturn['code'], 'errors' => $curlReturn['errors']];
        } elseif (empty($curlReturn['response']['users'])) {
            return [];
        }


        if (!empty($args['noFormat'])) {
            return $curlReturn['response']['users'];
        }

        $users = [];
        foreach ($curlReturn['response']['users'] as $user) {
            $users[] = [
                'idToDisplay' => trim($user['prenom'] . ' ' . $user['nom']),
                'email'       => trim($user['email'])
            ];
        }

        return $users;
    }

    /**
     * @param array $args
     * @return array|true
     * @throws Exception
     */
    public static function checkUserExistanceInFastParapheur(array $args): bool|array
    {
        ValidatorModel::notEmpty($args, ['fastParapheurUserEmail']);
        ValidatorModel::stringType($args, ['fastParapheurUserEmail']);

        $config = FastParapheurController::getConfig();
        if (!empty($config['errors'])) {
            return ['code' => $config['code'], 'errors' => $config['errors']];
        }

        $fpUsers = FastParapheurController::getUsers([
            'config' => [
                'subscriberId' => $config['subscriberId'],
                'url'          => $config['url'],
                'certPath'     => $config['certPath'],
                'certPass'     => $config['certPass'],
                'certType'     => $config['certType']
            ]
        ]);
        if (!empty($fpUsers['errors'])) {
            return ['code' => $fpUsers['code'], 'errors' => $fpUsers['errors']];
        } elseif (empty($fpUsers)) {
            return ['code' => 400, 'errors' => "FastParapheur users not found!"];
        }
        $fpUsersEmails = array_values(array_unique(array_column($fpUsers, 'email')));

        if (!in_array($args['fastParapheurUserEmail'], $fpUsersEmails)) {
            return ['code' => 400, 'errors' => "FastParapheur user '{$args['fastParapheurUserEmail']}' not found!"];
        }

        return true;
    }

    /**
     * @return array
     * @throws Exception
     */
    public static function getConfig(): array
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if (empty($loadedXml)) {
            return ['code' => 400, 'errors' => 'SignatoryBooks configuration file missing or empty'];
        }

        $fastParapheurBlock = $loadedXml->xpath('//signatoryBook[id=\'fastParapheur\']')[0] ?? null;
        if (empty($fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'FastParapheur configuration is missing'];
        }

        $fastParapheurBlock = json_decode(json_encode($fastParapheurBlock), true);
        if (empty($fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'invalid configuration for FastParapheur'];
        } elseif (!array_key_exists('workflowTypes', $fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'workflowTypes not found for FastParapheur'];
        } elseif (!array_key_exists('subscriberId', $fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'subscriberId not found for FastParapheur'];
        } elseif (!array_key_exists('url', $fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'url not found for FastParapheur'];
        } elseif (!array_key_exists('certPath', $fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'certPath not found for FastParapheur'];
        } elseif (!array_key_exists('certPass', $fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'certPass not found for FastParapheur'];
        } elseif (!array_key_exists('certType', $fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'certType not found for FastParapheur'];
        } elseif (!array_key_exists('validatedState', $fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'validatedState not found for FastParapheur'];
        } elseif (!array_key_exists('refusedState', $fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'refusedState not found for FastParapheur'];
        } elseif (!array_key_exists('optionOtp', $fastParapheurBlock)) {
            $fastParapheurBlock['optionOtp'] = 'false';
        }

        if (!array_key_exists('integratedWorkflow', $fastParapheurBlock)) {
            $fastParapheurBlock['integratedWorkflow'] = 'false';
        }

        return $fastParapheurBlock;
    }

    /**
     * @param array $args
     * @return string|string[]
     * @throws Exception
     */
    public static function getSummarySheetFile(array $args): array|string
    {
        ValidatorModel::notEmpty($args, ['docResId', 'login']);
        ValidatorModel::intVal($args, ['docResId']);

        $mainResource = ResModel::getOnView([
            'select' => ['*'],
            'where'  => ['res_id = ?'],
            'data'   => [$args['docResId']]
        ]);
        if (empty($mainResource)) {
            return ['error' => 'Mail does not exist'];
        }

        $units = [];
        $units[] = ['unit' => 'primaryInformations'];
        $units[] = ['unit' => 'secondaryInformations', 'label' => _SECONDARY_INFORMATION];
        $units[] = ['unit' => 'senderRecipientInformations', 'label' => _DEST_INFORMATION];
        $units[] = ['unit' => 'diffusionList', 'label' => _DIFFUSION_LIST];
        $units[] = ['unit' => 'visaWorkflow', 'label' => _VISA_WORKFLOW];
        $units[] = ['unit' => 'opinionWorkflow', 'label' => _AVIS_WORKFLOW];
        $units[] = ['unit' => 'notes', 'label' => _NOTES_COMMENT];

        // Data for resources
        $tmpIds = [$mainResource[0]['res_id']];
        $data = [];
        foreach ($units as $unit) {
            if ($unit['unit'] == 'notes') {
                $data['notes'] = NoteModel::get([
                    'select'   => ['id', 'note_text', 'user_id', 'creation_date', 'identifier'],
                    'where'    => ['identifier in (?)'],
                    'data'     => [$tmpIds],
                    'order_by' => ['identifier']
                ]);

                $userEntities = EntityModel::getByUserId(['userId' => $GLOBALS['id'], 'select' => ['entity_id']]);
                $data['userEntities'] = [];
                foreach ($userEntities as $userEntity) {
                    $data['userEntities'][] = $userEntity['entity_id'];
                }
            } elseif ($unit['unit'] == 'opinionWorkflow') {
                $data['listInstancesOpinion'] = ListInstanceModel::get([
                    'select'  => ['item_id', 'process_date', 'res_id'],
                    'where'   => ['difflist_type = ?', 'res_id in (?)'],
                    'data'    => ['AVIS_CIRCUIT', $tmpIds],
                    'orderBy' => ['listinstance_id']
                ]);
            } elseif ($unit['unit'] == 'visaWorkflow') {
                $data['listInstancesVisa'] = ListInstanceModel::get([
                    'select'  => ['item_id', 'requested_signature', 'process_date', 'res_id'],
                    'where'   => ['difflist_type = ?', 'res_id in (?)'],
                    'data'    => ['VISA_CIRCUIT', $tmpIds],
                    'orderBy' => ['listinstance_id']
                ]);
            } elseif ($unit['unit'] == 'diffusionList') {
                $data['listInstances'] = ListInstanceModel::get([
                    'select'  => ['item_id', 'item_type', 'item_mode', 'res_id'],
                    'where'   => ['difflist_type = ?', 'res_id in (?)'],
                    'data'    => ['entity_id', $tmpIds],
                    'orderBy' => ['listinstance_id']
                ]);
            }
        }

        $modelId = ResModel::getById([
            'select' => ['model_id'],
            'resId'  => $mainResource[0]['res_id']
        ]);
        $indexingFields = IndexingModelFieldModel::get([
            'select' => ['identifier', 'unit'],
            'where'  => ['model_id = ?'],
            'data'   => [$modelId['model_id']]
        ]);
        $fieldsIdentifier = array_column($indexingFields, 'identifier');

        $pdf = new Fpdi('P', 'pt');
        $pdf->setPrintHeader(false);

        SummarySheetController::createSummarySheet($pdf, [
            'resource'         => $mainResource[0],
            'units'            => $units,
            'login'            => $args['login'],
            'data'             => $data,
            'fieldsIdentifier' => $fieldsIdentifier
        ]);

        $tmpPath = CoreConfigModel::getTmpPath();
        $summarySheetFilePath = $tmpPath . "summarySheet_" . $args['docResId'] . "_" . $args['login'] . "_" . rand() .
            ".pdf";
        $pdf->Output($summarySheetFilePath, 'F');

        return $summarySheetFilePath;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getSignatureModes(array $args): array
    {
        ValidatorModel::boolType($args, ['mapping']);

        $config = FastParapheurController::getConfig();
        if (!empty($config['errors'])) {
            return ['code' => $config['code'], 'errors' => $config['errors']];
        }

        if (empty($config['signatureModes']['mode'])) {
            return ['code' => 400, 'errors' => "signatureModes not found in config file"];
        }

        //map sign to signature or others
        $modes = $config['signatureModes']['mode'];
        if (!empty($args['mapping'])) {
            $modes = [];
            foreach ($config['signatureModes']['mode'] as $key => $value) {
                $value['id'] = FastParapheurController::getSignatureModeById(['signatureModeId' => $value['id']]);
                $modes[] = $value;
            }
        }

        return ['signatureModes' => $modes];
    }

    /**
     * @param array $args
     * @return string
     * @throws Exception
     */
    public static function getSignatureModeById(array $args): string
    {
        ValidatorModel::notEmpty($args, ['signatureModeId']);
        ValidatorModel::stringType($args, ['signatureModeId']);

        $signatureModeId = "";
        switch ($args['signatureModeId']) {
            case 'sign':
                $signatureModeId = 'signature';
                break;

            case 'signature':
                $signatureModeId = 'sign';
                break;

            default:
                $signatureModeId = $args['signatureModeId'];
                break;
        }

        return $signatureModeId;
    }

    /**
    STANDBY : We can't create tiles for FAST

    public static function getResourcesCount(): int
    {
        $resourcesInFastParapheur = ResModel::get([
            'select' => ['res_id', 'external_id->>\'signatureBookId\' as "signatureBookId"'],
            'where'  => ['external_id->>\'signatureBookId\' is not null']
        ]);

        $attachmentsInFastParapheur = AttachmentModel::get([
            'select' => ['res_id', 'external_id->>\'signatureBookId\' as "signatureBookId"'],
            'where'  => ['external_id->>\'signatureBookId\' is not null']
        ]);

        $documentsInDataBase = array_merge($resourcesInFastParapheur, $attachmentsInFastParapheur);
        $documentsInFastParapheur = FastParapheurController::getResources();
        if (!empty($documentsInFastParapheur['errors'])) {
            return ['code' => $documentsInFastParapheur['code'], 'errors' => $documentsInFastParapheur['errors']];
        }

        $resourcesNumber = 0;
        foreach ($documentsInDataBase as $document) {
            if (array_search($document['signatureBookId'], $documentsInFastParapheur['response'])) {
                $resourcesNumber++;
            }
        }

        return $resourcesNumber;
    }

    public static function getResourcesDetails(): array
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if (empty($loadedXml)) {
            return ['errors' => 'configuration file missing'];
        }

        $fastParapheurBlock = $loadedXml->xpath('//signatoryBook[id=\'fastParapheur\']')[0] ?? null;
        if (empty($fastParapheurBlock)) {
            return ['errors' => 'invalid configuration for FastParapheur'];
        }
        $fastParapheurUrl = (string)$fastParapheurBlock->url;
        $fastParapheurUrl = str_replace('/parapheur-ws/rest/v1', '', $fastParapheurUrl);

        $resourcesInFastParapheur = ResModel::get([
            'select'  => [
                'external_id->>\'signatureBookId\' as "signatureBookId"',
                'subject',
                'creation_date',
                'res_id',
                'category_id'
            ],
            'where'   => ['external_id->>\'signatureBookId\' is not null'],
            'orderBy' => ['creation_date DESC']
        ]);

        $attachmentsInFastParapheur = AttachmentModel::get([
            'select'  => [
                'external_id->>\'signatureBookId\' as "signatureBookId"',
                'title as subject',
                'res_id',
                'creation_date'
            ],
            'where'   => ['external_id->>\'signatureBookId\' is not null'],
            'orderBy' => ['creation_date DESC']
        ]);
        $correspondents = null;
        $documentsInFastParapheur = FastParapheurController::getResources();
        if (!empty($documentsInFastParapheur['errors'])) {
            return ['code' => $documentsInFastParapheur['code'], 'errors' => $documentsInFastParapheur['errors']];
        }

        $documentsInDataBase = array_merge($resourcesInFastParapheur, $attachmentsInFastParapheur);
        foreach ($documentsInDataBase as $document) {
            if (!(array_search($document['signatureBookId'], $documentsInFastParapheur['response']))) {
                unset($documentsInDataBase[array_search($document, $documentsInDataBase)]);
            }
        }
        $documentsInDataBase = array_values(array_map(function ($doc) use ($fastParapheurUrl) {
            if ($doc['category_id'] == 'outgoing') {
                $correspondents = ContactController::getFormattedContacts(
                    ['resId' => $doc['res_id'], 'mode' => 'recipient', 'onlyContact' => true]
                );
            } else {
                $correspondents = ContactController::getFormattedContacts(
                    ['resId' => $doc['res_id'], 'mode' => 'sender', 'onlyContact' => true]
                );
            }
            return [
                'subject'        => $doc['subject'],
                'creationDate'   => $doc['creation_date'],
                'correspondents' => $correspondents,
                'resId'          => (int)$doc['signatureBookId'],
                'url'            => $fastParapheurUrl . '/parapheur/showDoc.action?documentid=' .
                    $doc['signatureBookId']
            ];
        }, $documentsInDataBase));

        return $documentsInDataBase;
    }

    public static function getResources(): array
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if (empty($loadedXml)) {
            return ['code' => 400, 'errors' => 'SignatoryBooks configuration file missing'];
        }

        $fastParapheurBlock = $loadedXml->xpath('//signatoryBook[id=\'fastParapheur\']')[0] ?? null;
        if (empty($fastParapheurBlock)) {
            return ['code' => 500, 'errors' => 'invalid configuration for FastParapheur'];
        }
        $url = (string)$fastParapheurBlock->url;
        $certPath = (string)$fastParapheurBlock->certPath;
        $certPass = (string)$fastParapheurBlock->certPass;
        $certType = (string)$fastParapheurBlock->certType;
        $subscriberId = (string)$fastParapheurBlock->subscriberId;

        $curlReturn = CurlModel::exec([
            'url'     => $url . '/documents/search',
            'method'  => 'POST',
            'headers' => [
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            'options' => [
                CURLOPT_SSLCERT       => $certPath,
                CURLOPT_SSLCERTPASSWD => $certPass,
                CURLOPT_SSLCERTTYPE   => $certType
            ],
            'body'    => json_encode([
                'siren'   => $subscriberId,
                'state'   => 'Prepared',
                'circuit' => 'circuit-a-la-volee'
            ])
        ]);

        if ($curlReturn['code'] == 404) {
            return ['code' => 404, 'errors' => 'Erreur 404 : ' . $curlReturn['raw']];
        } elseif (!empty($curlReturn['errors'])) {
            return ['code' => $curlReturn['code'], 'errors' => $curlReturn['errors']];
        } elseif (!empty($curlReturn['response']['developerMessage'])) {
            return ['code' => $curlReturn['code'], 'errors' => $curlReturn['response']['developerMessage']];
        }

        return ['response' => $curlReturn['response']];
    }
    */

    /**
     * @param array $documentHistory
     * @param array $knownWorkflow
     * @param array $config
     * @return array
     * @throws Exception
     */
    public static function getLastFastWorkflowAction(array $documentHistory, array $knownWorkflow, array $config): array
    {
        ValidatorModel::notEmpty($config, ['validatedState', 'validatedVisaState', 'refusedState', 'refusedVisaState']);
        ValidatorModel::stringType(
            $config,
            ['validatedState', 'validatedVisaState', 'refusedState', 'refusedVisaState']
        );

        if (empty($knownWorkflow) || empty($documentHistory)) {
            return [];
        }

        $totalStepsInWorkflow = count($knownWorkflow);
        $current = 0;
        $lastStep = [];

        foreach ($documentHistory as $historyStep) {
            if (!empty($knownWorkflow[$current]['id'])) {
                $historyStep['userFastId'] = $knownWorkflow[$current]['id'];
            }
            // If the document has been refused, then the workflow has ended and the last step is the refused step
            if (in_array($historyStep['stateName'], [$config['refusedState'], $config['refusedVisaState']])) {
                $lastStep = $historyStep;
                break;
            }

            // If the state is sign or an approved visa, the workflow is continuing
            if (in_array($historyStep['stateName'], [$config['validatedState'], $config['validatedVisaState']])) {
                $current++;

                // If we have as many steps in history as the workflow,
                // then the workflow is over and the last step is the last sign/visa
                if ($current === $totalStepsInWorkflow) {
                    $lastStep = $historyStep;
                    break;
                }
            }
        }

        return $lastStep;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function generateOtpXml(array $args): array
    {
        ValidatorModel::notEmpty($args, ['otpInfo']);
        ValidatorModel::arrayType($args, ['otpInfo']);
        ValidatorModel::boolType($args, ['prettyPrint']);

        $xmlData = null;
        try {
            $otpInfoXML = new SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?> <meta-data-list></meta-data-list>'
            );
            foreach ($args['otpInfo'] as $name => $value) {
                $metaDataElement = $otpInfoXML->addChild('meta-data');
                $metaDataElement->addAttribute('name', $name);
                $metaDataElement->addAttribute('value', $value);
            }

            $xmlData = $otpInfoXML->asXML();
            if (!empty($args['prettyPrint'])) {
                $dom = dom_import_simplexml($otpInfoXML)->ownerDocument;
                $dom->formatOutput = true;
                $xmlData = $dom->saveXML();
            }
        } catch (Exception $e) {
            return ['errors' => '[FastParapheur][generateOtpXml] : ' . $e->getMessage()];
        }

        return ['content' => $xmlData];
    }

    /**
     * @return array|bool
     * @throws Exception
     */
    public static function isOtpActive(): array|bool
    {
        $config = FastParapheurController::getConfig();
        if (!empty($config['errors'])) {
            return ['code' => $config['code'], 'errors' => $config['errors']];
        }

        if (filter_var($config['optionOtp'], FILTER_VALIDATE_BOOLEAN)) {
            return ['OTP' => true];
        }
        return ['OTP' => false];
    }

    /**
     * Generate a xml configuration from {@see FastParapheurController::prepareStampsSteps}
     *
     * @param array $pictogrammes
     *
     * @return ?string
     */
    public static function generateXmlPictogramme(array $pictogrammes = []): ?string
    {
        if (empty($pictogrammes)) {
            return null;
        }

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><pictogrammes></pictogrammes>'
        );

        foreach ($pictogrammes as $type => $pictograms) {
            $typeElement = $xml->addChild($type);

            foreach ($pictograms as $config) {
                $pictogramme = $typeElement->addChild('pictogramme');
                $pictogramme->addAttribute('index', $config['index']);
                $pictogramme->addAttribute('border', $config['border']);
                $pictogramme->addAttribute('opacite', $config['opacite']);
                $pictogramme->addAttribute('font-size', $config['font-size']);

                if (isset($config['position'])) {
                    $position = $pictogramme->addChild('position');
                    foreach ($config['position'] as $attr => $value) {
                        $position->addAttribute($attr, $value);
                    }
                }

                foreach (['top', 'center', 'bottom', 'left', 'right'] as $position) {
                    if (isset($config[$position])) {
                        $posElement = $pictogramme->addChild($position);
                        foreach ($config[$position] as $metadata) {
                            $meta = $posElement->addChild('metadata');
                            $meta->addAttribute('name', $metadata['name']);
                            if (!empty($metadata['value'])) {
                                $meta->addAttribute('value', $metadata['value']);
                            }
                        }
                    }
                }
            }
        }

        $xmlResult = $xml->asXML();

        if ($xmlResult === false) {
            return null;
        }
        return $xmlResult;
    }
}
