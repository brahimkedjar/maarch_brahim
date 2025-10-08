<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MwsController
 * @author dev@maarch.org
 */

namespace Mercure\controllers;

use Attachment\models\AttachmentModel;
use Configuration\models\ConfigurationModel;
use Docserver\models\DocserverModel;
use Exception;
use Group\controllers\PrivilegeController;
use Resource\models\ResModel;
use SrcCore\controllers\LogsController;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\ValidatorModel;

class MwsController
{
    /**
     * @return array
     */
    private function getMwsConfiguration(): array
    {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        if (empty($configuration)) {
            return [
                'code'   => 400,
                'errors' => 'Mercure configuration is not enabled'
            ];
        }

        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration['mws']['url'])) {
            return [
                'code'   => 400,
                'errors' => 'Mercure configuration URI is empty',
                'config' => $configuration
            ];
        }
        $mwsUri = rtrim($configuration['mws']['url'], '/');

        return [
            'url'            => $mwsUri,
            'login'          => $configuration['mws']['login'],
            'password'       => $configuration['mws']['password'],
            'loginMaarch'    => $configuration['mws']['loginMaarch'],
            'passwordMaarch' => $configuration['mws']['passwordMaarch']
        ];
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws Exception
     */
    public function checkAccount(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_mercure', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $mwsConfig = MwsController::getMwsConfiguration();
        if (isset($mwsConfig['errors'])) {
            return $response->withStatus($mwsConfig['code'])->withJson([
                'errors'    => $mwsConfig['errors'],
                'mwsConfig' => $mwsConfig
            ]);
        }

        $body = (object)[
            'username' => $mwsConfig['login'],
            'password' => $mwsConfig['password']
        ];

        $curlResponse = CurlModel::exec([
            'url'     => "{$mwsConfig['url']}/api/login_check",
            'headers' => ['content-type:application/json'],
            'method'  => 'GET',
            'body'    => json_encode($body)
        ]);

        if ($curlResponse['code'] != 200) {
            if ($curlResponse['code'] == 404) {
                return $response->withStatus(404)->withJson(
                    ['errors' => 'Page not found', 'lang' => 'pageNotFound']
                );
            } elseif ($curlResponse['code'] == 400) {
                return $response->withStatus(400)->withJson([
                    'errors' => 'Identifiants invalides',
                    'lang'   => 'invalidCredentials'
                ]);
            } elseif (!empty($curlResponse['response'])) {
                return $response->withStatus(400)->withJson(['errors' => json_encode($curlResponse['response'])]);
            } else {
                return $response->withStatus(400)->withJson(['errors' => $curlResponse['errors']]);
            }
        }

        return $response->withJson(['token' => $curlResponse['response']['token'], 'username' => $mwsConfig['login']]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $aArgs
     *
     * @return Response
     * @throws Exception
     */
    public function loadListDocs(Request $request, Response $response, array $aArgs): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_mercure', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $mwsConfig = MwsController::getMwsConfiguration();
        if (isset($mwsConfig['errors'])) {
            return $response->withStatus($mwsConfig['code'])->withJson(['errors' => $mwsConfig['errors']]);
        }

        $curlResponse = CurlModel::exec([
            'url'         => "{$mwsConfig['url']}/api/depots",
            'headers'     => ['content-type:application/json', 'Authorization:Bearer ' . $aArgs['token']],
            'method'      => 'GET',
            'queryParams' => [
                'pagination'       => 'false',
                'userId.username'  => $mwsConfig['login'],
                'order[createdAt]' => 'desc'
            ]
        ]);

        return $response->withJson([
            'docs'    => $curlResponse['response']['hydra:member'],
            'nbTotal' => $curlResponse['response']['hydra:totalItems']
        ]);
    }

    /**
     * @param array $aArgs
     *
     * @return array|string[]
     * @throws Exception
     */
    public static function launchOcrMws(array $aArgs): array
    {
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
            return ['errors' => '[MwsController] Resource does not exist'];
        }

        $docserver = DocserverModel::getByDocserverId([
            'docserverId' => $resource['docserver_id'],
            'select'      => ['path_template']
        ]);

        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => '[MwsController] Docserver does not exist'];
        }

        $pathToDocument = $docserver['path_template'] .
            str_replace('#', DIRECTORY_SEPARATOR, $resource['path']) . $resource['filename'];

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        $configuration = json_decode($configuration['value'], true);

        $body = (object)[
            'encodedFile' => base64_encode(file_get_contents($pathToDocument)),
            'filename'    => basename($pathToDocument),
            'method'      => 'CONVERT'
        ];

        $curlResponse = CurlModel::exec([
            'url'     => "{$configuration['mws']['url']}api/newFile",
            'headers' => [
                'content-type:application/json',
                'Authorization:Bearer ' . $configuration['mws']['tokenMws']
            ],
            'method'  => 'POST',
            'body'    => json_encode($body)
        ]);

        $tmpFileOcr = CoreConfigModel::getTmpPath() . basename($pathToDocument, ".pdf") . rand() . ".pdf";

        file_put_contents($tmpFileOcr, base64_decode($curlResponse['response']['encodedFile']));

        if (is_file($tmpFileOcr)) {
            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'mercure',
                'level'     => 'ERROR',
                'tableName' => $tablename,
                'recordId'  => $aArgs['resId'],
                'eventType' => "OCR MWS - Convert to tiff : Error during fileTmp creation",
                'eventId'   => "response : " . json_encode($curlResponse)
            ]);

            return [
                'errors' => '[MwsController] Error during fileTmp creation',
                'body'   => $body,
                'output' => $curlResponse
            ];
        }

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'mercure',
            'level'     => 'INFO',
            'tableName' => $tablename,
            'recordId'  => $aArgs['resId'],
            'eventType' => "OCR MWS request success",
            'eventId'   => "document : {$tmpFileOcr}"
        ]);

        return ['convertedFile' => $tmpFileOcr];
    }

    /**
     * @param array $aArgs
     *
     * @return array
     * @throws Exception
     */
    public static function launchLadMws(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['encodedResource', 'filename']);
        ValidatorModel::stringType($aArgs, ['encodedResource', 'filename']);

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        $configuration = json_decode($configuration['value'], true);

        $body = (object)[
            'encodedFile' => $aArgs['encodedResource'],
            'filename'    => $aArgs['filename'],
            'method'      => 'EXTRACT_LAD_VALUES',
            'type'        => 'COURRIER'
        ];

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'mercure',
            'level'     => 'INFO',
            'tableName' => '',
            'recordId'  => '',
            'eventType' => "MWS LAD task",
            'eventId'   => "Launch MWS LAD on file {$aArgs['filename']}"
        ]);

        $curlResponse = CurlModel::exec([
            'url'     => "{$configuration['mws']['url']}api/newFile",
            'headers' => [
                'content-type:application/json',
                'Authorization:Bearer ' . $configuration['mws']['tokenMws']
            ],
            'method'  => 'POST',
            'body'    => json_encode($body)
        ]);

        $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => 'config/ladConfiguration.json']);
        $mappingMercure = $ladConfiguration['mappingLadFields'];

        $aReturn = [];
        foreach ($curlResponse['response']['ladInformations'][0] as $nameField => $valueField) {
            if (isset($mappingMercure[$nameField])) {
                $disabledField = false;
                $returnNameAttribute = $nameField;

                if (isset($mappingMercure[$nameField]['key'])) {
                    $returnNameAttribute = $mappingMercure[$nameField]['key'];
                }

                if (isset($mappingMercure[$nameField]['disabled'])) {
                    $disabledField = $mappingMercure[$nameField]['disabled'];
                }

                if (
                    !$disabledField &&
                    (!array_key_exists($returnNameAttribute, $aReturn) || empty($aReturn[$returnNameAttribute]))
                ) {
                    $aReturn[$returnNameAttribute] = $valueField;
                }
            }
        }

        if (!empty($curlResponse['response']['encodedFile'])) {
            $tmpFileOcr = CoreConfigModel::getTmpPath() . "OCRFile_" . $aArgs['filename'];
            file_put_contents($tmpFileOcr, base64_decode($curlResponse['response']['encodedFile']));
        }

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'mercure',
            'level'     => 'INFO',
            'tableName' => '',
            'recordId'  => '',
            'eventType' => "MWS LAD task",
            'eventId'   => "MWS LAD task success on file {$aArgs['filename']}"
        ]);
        return $aReturn;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws Exception
     */
    public static function loadSubscriptionState(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_mercure', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        $configuration = json_decode($configuration['value'], true);

        $curlResponse = CurlModel::exec([
            'url'     => "{$configuration['mws']['url']}api/subscribe/status",
            'headers' => [
                'content-type:application/json',
                'Authorization:Bearer ' . $configuration['mws']['tokenMws']
            ],
            'method'  => 'GET'
        ]);

        if ($curlResponse['code'] == 204) {
            return $response->withStatus(204)->withJson(['errors' => 'Aucun abonnement pour cet utilisateur']);
        }
        return $response->withJson($curlResponse['response']);
    }
}
