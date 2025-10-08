<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief History Model
 * @author dev@maarch.org
 */

namespace History\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

abstract class HistoryModelAbstract
{
    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function get(array $args): array
    {
        ValidatorModel::notEmpty($args, ['select']);
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intVal($args, ['offset', 'limit']);

        return DatabaseModel::select([
            'select'   => $args['select'],
            'table'    => ['history'],
            'where'    => $args['where'] ?? [],
            'data'     => $args['data'] ?? [],
            'order_by' => $args['orderBy'] ?? [],
            'offset'   => $args['offset'] ?? 0,
            'limit'    => $args['limit'] ?? 0
        ]);
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function create(array $args): bool
    {
        ValidatorModel::notEmpty(
            $args,
            ['tableName', 'recordId', 'eventType', 'userId', 'info', 'moduleId', 'eventId']
        );
        ValidatorModel::stringType($args, ['tableName', 'eventType', 'info', 'moduleId', 'eventId']);
        ValidatorModel::intVal($args, ['userId']);

        DatabaseModel::insert([
            'table'         => 'history',
            'columnsValues' => [
                'table_name' => $args['tableName'],
                'record_id'  => $args['recordId'],
                'event_type' => $args['eventType'],
                'user_id'    => $args['userId'],
                'event_date' => 'CURRENT_TIMESTAMP',
                'info'       => $args['info'],
                'id_module'  => $args['moduleId'],
                'remote_ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
                'event_id'   => $args['eventId']
            ]
        ]);

        return true;
    }
}
