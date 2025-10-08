<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief List Instance History Detail Model Abstract
 * @author dev@maarch.org
 */

namespace Entity\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class ListInstanceHistoryDetailModel
{
    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     */
    public static function get(array $args): array
    {
        ValidatorModel::notEmpty($args, ['select']);
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy', 'groupBy']);
        ValidatorModel::intType($args, ['limit']);

        return DatabaseModel::select([
            'select'    => $args['select'],
            'table'     => ['listinstance_history_details'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'order_by'  => empty($args['orderBy']) ? [] : $args['orderBy'],
            'groupBy'   => empty($args['groupBy']) ? [] : $args['groupBy'],
            'limit'     => empty($args['limit']) ? 0 : $args['limit']
        ]);
    }

    /**
     * @param array $args
     *
     * @return bool
     * @throws Exception
     */
    public static function create(array $args): bool
    {
        ValidatorModel::notEmpty($args, [
            'listinstance_history_id',
            'res_id',
            'item_id',
            'item_type',
            'item_mode',
            'added_by_user',
            'difflist_type'
        ]);
        ValidatorModel::intVal($args, ['listinstance_history_id', 'res_id', 'sequence', 'item_id', 'added_by_user']);
        ValidatorModel::stringType(
            $args,
            ['item_type', 'item_mode', 'difflist_type', 'process_date', 'process_comment']
        );

        DatabaseModel::insert([
            'table'         => 'listinstance_history_details',
            'columnsValues' => [
                'listinstance_history_id'   => $args['listinstance_history_id'],
                'coll_id'                   => 'letterbox_coll',
                'res_id'                    => $args['res_id'],
                'sequence'                  => $args['sequence'],
                'item_id'                   => $args['item_id'],
                'item_type'                 => $args['item_type'],
                'item_mode'                 => $args['item_mode'],
                'added_by_user'             => $args['added_by_user'],
                'viewed'                    => 0,
                'difflist_type'             => $args['difflist_type'],
                'process_date'              => $args['process_date'],
                'process_comment'           => $args['process_comment'],
                'requested_signature'       => empty($args['requested_signature']) ? 'false' : 'true',
                'signatory'                 => empty($args['signatory']) ? 'false' : 'true'
            ]
        ]);

        return true;
    }
}
