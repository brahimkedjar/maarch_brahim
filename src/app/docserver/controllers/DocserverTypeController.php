<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief DocerverType Controller
 * @author dev@maarch.org
 */

namespace Docserver\controllers;

use Docserver\models\DocserverTypeModel;
use Group\controllers\PrivilegeController;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class DocserverTypeController
{
    public const FORBIDDEN_TYPE_IDS_FOR_ENCRYPTION = ['FULLTEXT', 'MIGRATION'];

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function get(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_docservers', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        return $response->withJson(
            ['docserverTypes' => DocserverTypeModel::get(['orderBy' => ['docserver_type_label']])]
        );
    }
}
