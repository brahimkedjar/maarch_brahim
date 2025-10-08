<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Online Editor Controller
 *
 * @author dev@maarch.org
 */

namespace ContentManagement\controllers;

use Configuration\models\ConfigurationModel;
use Exception;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\models\ValidatorModel;

class DocumentEditorController
{
    public const DOCUMENT_EDITION_METHODS = ['java', 'onlyoffice', 'collaboraonline', 'office365sharepoint'];

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public static function get(Request $request, Response $response): Response
    {
        $allowedMethods = DocumentEditorController::getAllowedMethods();

        return $response->withJson($allowedMethods);
    }

    /**
     * @return array
     */
    public static function getAllowedMethods(): array
    {
        $configuration = ConfigurationModel::getByPrivilege([
            'privilege' => 'admin_document_editors',
            'select'    => ['value']
        ]);
        $configuration = !empty($configuration['value']) ? json_decode($configuration['value'], true) : [];

        $allowedMethods = [];
        $default = $configuration['default'] ?? '';
        unset($configuration['default']);
        foreach ($configuration as $key => $method) {
            $allowedMethods[] = $key;
        }

        $allowedMethods['default'] = $default;

        return $allowedMethods;
    }

    /**
     * @param array $args
     * @return array|bool
     * @throws Exception
     */
    public static function isAvailable(array $args): array|bool
    {
        ValidatorModel::notEmpty($args, ['uri', 'port']);
        ValidatorModel::stringType($args, ['uri']);
        ValidatorModel::intType($args, ['port']);

        $uri = $args['uri'] ?? null;

        if (!DocumentEditorController::uriIsValid($uri)) {
            return [
                'errors' => "Editor 'uri' is not a valid URL or IP address format",
                'lang'   => 'editorHasNoValidUrlOrIp'
            ];
        }

        $aUri = explode("/", $args['uri']);
        $exec = shell_exec("nc -vz -w 5 {$aUri[0]} {$args['port']} 2>&1");

        if (str_contains($exec, 'not found')) {
            return ['errors' => 'Netcat command not found', 'lang' => 'preRequisiteMissing'];
        }

        return str_contains($exec, 'succeeded!') || str_contains($exec, 'open') || str_contains($exec, 'Connected');
    }

    /**
     * @param $args
     * @return bool|null
     */
    public static function uriIsValid($args): ?bool
    {
        $whitelist = '/^(?:\w+(?:\/)?|' .
            '(?:https?:\/\/)?((?:[\da-z.-]+)\.' .
            '(?:[a-z.]{2,6})|(?:\d{1,3}\.){3}\d{1,3})' .
            '(?:[\/\w.-]*)*\/?)$/i';
        return preg_match($whitelist, $args);
    }
}
