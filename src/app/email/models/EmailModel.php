<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Configuration Model
 * @author dev@maarch.org
 */

namespace Email\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class EmailModel
{
    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function get(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit', 'offset']);

        return DatabaseModel::select([
            'select'   => empty($args['select']) ? ['*'] : $args['select'],
            'table'    => ['emails'],
            'where'    => empty($args['where']) ? [] : $args['where'],
            'data'     => empty($args['data']) ? [] : $args['data'],
            'order_by' => empty($args['orderBy']) ? [] : $args['orderBy'],
            'offset'   => empty($args['offset']) ? 0 : $args['offset'],
            'limit'    => empty($args['limit']) ? 0 : $args['limit']
        ]);
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getById(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::arrayType($aArgs, ['select']);

        $email = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['emails'],
            'where'  => ['id = ?'],
            'data'   => [$aArgs['id']],
        ]);

        if (empty($email[0])) {
            return [];
        }

        return $email[0];
    }

    /**
     * @param array $aArgs
     * @return int
     * @throws Exception
     */
    public static function create(array $aArgs): int
    {
        ValidatorModel::notEmpty($aArgs, ['userId', 'sender', 'recipients', 'cc', 'cci', 'isHtml', 'status']);
        ValidatorModel::intVal($aArgs, ['userId']);
        ValidatorModel::stringType(
            $aArgs,
            ['sender', 'recipients', 'cc', 'cci', 'object', 'body', 'messageExchangeId', 'document', 'isHtml', 'status']
        );

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'emails_id_seq']);

        DatabaseModel::insert([
            'table'         => 'emails',
            'columnsValues' => [
                'id'                  => $nextSequenceId,
                'user_id'             => $aArgs['userId'],
                'sender'              => $aArgs['sender'],
                'recipients'          => $aArgs['recipients'],
                'cc'                  => $aArgs['cc'],
                'cci'                 => $aArgs['cci'],
                'object'              => $aArgs['object'],
                'body'                => $aArgs['body'],
                'document'            => $aArgs['document'],
                'is_html'             => $aArgs['isHtml'],
                'status'              => $aArgs['status'],
                'message_exchange_id' => $aArgs['messageExchangeId'],
                'creation_date'       => 'CURRENT_TIMESTAMP'
            ]
        ]);

        return $nextSequenceId;
    }

    /**
     * @param array $aArgs
     * @return true
     * @throws Exception
     */
    public static function update(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['set', 'where', 'data']);
        ValidatorModel::arrayType($aArgs, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'emails',
            'set'   => $aArgs['set'],
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return true
     * @throws Exception
     */
    public static function delete(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['where', 'data']);
        ValidatorModel::arrayType($aArgs, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'emails',
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }
}
