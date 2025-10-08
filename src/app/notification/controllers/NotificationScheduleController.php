<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Notifications Schedule Controller
 *
 * @author dev@maarch.org
 */

namespace Notification\controllers;

use Exception;
use Group\controllers\PrivilegeController;
use Respect\Validation\Validator;
use Notification\models\NotificationModel;
use Notification\models\NotificationScheduleModel;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;

class NotificationScheduleController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function get(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_notif', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        return $response->withJson([
            'crontab'                => NotificationScheduleModel::getCrontab(),
            'authorizedNotification' => NotificationScheduleController::getAuthorizedNotifications(),
        ]);
    }

    // Save Crontab

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function create(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_notif', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParsedBody();
        if (!NotificationScheduleController::checkCrontab($data)) {
            return $response->withStatus(500)->withJson(['errors' => 'Problem with crontab']);
        }

        foreach ($data as $cronValue) {
            foreach ($cronValue as $key => $value) {
                if (($key == 'cmd' || $key == 'state') && !Validator::notEmpty()->validate($value)) {
                    $errors[] = $key . ' is empty';
                }

                if (
                    $key != 'cmd' && $key != 'state' && $key != 'description' &&
                    !preg_match('#^[0-9\/*][0-9]?[,\/-]?([0-9]?){2}#', $value)
                ) {
                    $errors[] = 'wrong format for ' . $key;
                }
            }
        }
        if (!empty($errors)) {
            return $response->withStatus(500)->withJson(['errors' => $errors]);
        }

        NotificationScheduleModel::saveCrontab(['crontab' => $data]);

        return $response->withJson(true);
    }

    /**
     * @return array
     * @throws Exception
     */
    protected static function getAuthorizedNotifications(): array
    {
        $aNotification = NotificationModel::getEnableNotifications([
            'select' => ['notification_id', 'notification_sid', 'description']
        ]);
        $notificationsArray = [];
        $customId = CoreConfigModel::getCustomId();
        $corePath = str_replace(
            'custom/' . $customId . '/src/app/notification/controllers',
            '',
            __DIR__
        );
        $corePath = str_replace('src/app/notification/controllers', '', $corePath);

        foreach ($aNotification as $result) {
            $filename = 'notification';
            if (isset($customId) && $customId != '') {
                $filename .= '_' . str_replace(' ', '', $customId);
            }
            $filename .= '_' . $result['notification_id'] . '.sh';

            if ($customId != '') {
                $pathToFollow = $corePath . 'custom/' . $customId . '/';
            } else {
                $pathToFollow = $corePath;
            }

            $path = $pathToFollow . 'bin/notification/scripts/' . $filename;
            if (file_exists($path)) {
                $notificationsArray[] = ['description' => $result['description'], 'path' => $path];
            }
        }

        return $notificationsArray;
    }

    /**
     * @param $crontabToSave
     * @return bool
     */
    protected static function checkCrontab($crontabToSave): bool
    {
        $customId = CoreConfigModel::getCustomId();
        $crontabBeforeSave = NotificationScheduleModel::getCrontab();
        $corePath = str_replace(
            'custom/' . $customId . '/src/app/notification/controllers',
            '',
            __DIR__
        );
        $corePath = str_replace('src/app/notification/controllers', '', $corePath);

        $returnValue = false;
        foreach ($crontabToSave as $id => $cronValue) {
            $crontabBeforeSave[$id] = $crontabBeforeSave[$id] ?? null;
            if (
                $cronValue['state'] != 'hidden' && $crontabBeforeSave[$id] != null &&
                $crontabBeforeSave[$id]['state'] == 'hidden'
            ) {
                $returnValue = false;
                break;
            } elseif (
                $cronValue['state'] == 'hidden' && $crontabBeforeSave[$id] != null &&
                $crontabBeforeSave[$id]['state'] != 'hidden'
            ) {
                $returnValue = false;
                break;
            } elseif ($cronValue['state'] == 'new' || $cronValue['state'] == 'normal') {
                if ($customId != '') {
                    $pathToFollow = $corePath . 'custom/' . $customId . '/';
                } else {
                    $pathToFollow = $corePath;
                }
                $returnValue = true;
                if (!str_starts_with($cronValue['cmd'], $pathToFollow . 'bin/notification/scripts/')) {
                    $returnValue = false;
                    break;
                }
            } else {
                $returnValue = true;
            }
        }

        return $returnValue;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function createScriptNotification(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_notif', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $errors = [];
        $data = $request->getParsedBody();
        if (!Validator::intVal()->validate($data['notification_sid'])) {
            $errors[] = 'notification_sid is not a numeric';
        }
        if (
            !Validator::notEmpty()->validate($data['notification_sid']) ||
            !Validator::notEmpty()->validate($data['notification_id']) ||
            !Validator::notEmpty()->validate($data['event_id'])
        ) {
            $errors[] = 'one of arguments is empty';
        }

        if (!empty($errors)) {
            return $response->withStatus(500)->withJson(['errors' => $errors]);
        }

        $notification_sid = $data['notification_sid'];
        $notification_id = $data['notification_id'];
        $event_id = $data['event_id'];

        NotificationScheduleModel::createScriptNotification(
            ['notification_sid' => $notification_sid, 'event_id' => $event_id, 'notification_id' => $notification_id]
        );

        return $response->withJson(true);
    }
}
