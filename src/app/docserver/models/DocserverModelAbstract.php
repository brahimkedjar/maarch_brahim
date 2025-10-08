<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Docserver Model
* @author dev@maarch.org
* @ingroup core
*/

namespace Docserver\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class DocserverModelAbstract
{
    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function get(array $aArgs = []): array
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($aArgs, ['limit']);

        return DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['docservers'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
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

        $aDocserver = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['docservers'],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        if (empty($aDocserver[0])) {
            return [];
        }

        return $aDocserver[0];
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getByDocserverId(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['docserverId']);
        ValidatorModel::stringType($aArgs, ['docserverId']);
        ValidatorModel::arrayType($aArgs, ['select']);

        $aDocserver = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['docservers'],
            'where'     => ['docserver_id = ?'],
            'data'      => [$aArgs['docserverId']]
        ]);

        if (empty($aDocserver[0])) {
            return [];
        }

        return $aDocserver[0];
    }

    /**
     * @param array $aArgs
     * @return int
     * @throws Exception
     */
    public static function create(array $aArgs): int
    {
        ValidatorModel::notEmpty($aArgs, [
            'docserver_id',
            'docserver_type_id',
            'device_label',
            'path_template',
            'coll_id',
            'size_limit_number',
            'is_readonly'
        ]);
        ValidatorModel::stringType($aArgs, [
            'docserver_id',
            'docserver_type_id',
            'device_label',
            'path_template',
            'coll_id',
            'is_readonly'
        ]);
        ValidatorModel::intVal($aArgs, ['size_limit_number']);
        ValidatorModel::boolType($aArgs, ['is_encrypted']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'docservers_id_seq']);

        DatabaseModel::insert([
            'table'         => 'docservers',
            'columnsValues' => [
                'id'                    => $nextSequenceId,
                'docserver_id'          => $aArgs['docserver_id'],
                'docserver_type_id'     => $aArgs['docserver_type_id'],
                'device_label'          => $aArgs['device_label'],
                'path_template'         => $aArgs['path_template'],
                'coll_id'               => $aArgs['coll_id'],
                'size_limit_number'     => $aArgs['size_limit_number'],
                'is_readonly'           => $aArgs['is_readonly'],
                'creation_date'         => 'CURRENT_TIMESTAMP',
                'is_encrypted'          => empty($aArgs['is_encrypted']) ? 'false' : 'true',
            ]
        ]);

        return $nextSequenceId;
    }

    /**
     * @param array $aArgs
     * @return bool
     * @throws Exception
     */
    public static function update(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['set', 'where', 'data']);
        ValidatorModel::arrayType($aArgs, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'docservers',
            'set'   => $aArgs['set'],
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return bool
     * @throws Exception
     */
    public static function delete(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        DatabaseModel::delete([
            'table'     => 'docservers',
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getCurrentDocserver(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['collId', 'typeId']);
        ValidatorModel::stringType($aArgs, ['collId', 'typeId']);

        $aDocserver = DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['docservers'],
            'where'     => ['is_readonly = ?', 'coll_id = ?', 'docserver_type_id = ?'],
            'data'      => ['N', $aArgs['collId'], $aArgs['typeId']],
            'limit'     => 1,
        ]);

        if (empty($aDocserver[0])) {
            return [];
        }

        return $aDocserver[0];
    }
}
