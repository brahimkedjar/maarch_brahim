<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 * @brief   FolderModelAbstract
 * @author  dev <dev@maarch.org>
 * @ingroup core
 */

namespace Folder\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class FolderModelAbstract
{
    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function get(array $aArgs): array
    {
        return DatabaseModel::select([
            'select'   => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'    => ['folders'],
            'where'    => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'     => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by' => empty($aArgs['orderBy']) ? ['label'] : $aArgs['orderBy']
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

        $folder = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['folders', 'entities_folders'],
            'left_join' => ['folders.id = entities_folders.folder_id'],
            'where'     => ['folders.id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        if (empty($folder[0])) {
            return [];
        }

        return $folder[0];
    }

    /**
     * @param array $aArgs
     * @return array|mixed
     * @throws Exception
     */
    public static function getFolderPath(array $aArgs): mixed
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        if (empty($aArgs['folderPath'])) {
            $aArgs['folderPath'] = [];
        }

        $currentFolder = FolderModel::getById(['select' => ['parent_id', 'label'], 'id' => $aArgs['id']]);
        array_unshift($aArgs['folderPath'], $currentFolder['label']);
        if (!empty($currentFolder['parent_id'])) {
            return FolderModel::getFolderPath([
                'id'         => $currentFolder['parent_id'],
                'folderPath' => $aArgs['folderPath']
            ]);
        }

        return $aArgs['folderPath'];
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getChild(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        return DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['folders'],
            'where'  => ['parent_id = ?'],
            'data'   => [$aArgs['id']]
        ]);
    }

    /**
     * @param array $aArgs
     * @return int
     * @throws Exception
     */
    public static function create(array $aArgs): int
    {
        ValidatorModel::notEmpty($aArgs, ['user_id', 'label']);
        ValidatorModel::stringType($aArgs, ['label']);
        ValidatorModel::intVal($aArgs, ['user_id', 'parent_id', 'level']);
        ValidatorModel::boolType($aArgs, ['public']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'folders_id_seq']);

        DatabaseModel::insert([
            'table'         => 'folders',
            'columnsValues' => [
                'id'        => $nextSequenceId,
                'label'     => $aArgs['label'],
                'public'    => empty($aArgs['public']) ? 'false' : 'true',
                'user_id'   => $aArgs['user_id'],
                'parent_id' => $aArgs['parent_id'],
                'level'     => $aArgs['level']
            ]
        ]);

        return $nextSequenceId;
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function update(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'folders',
            'set'   => empty($args['set']) ? [] : $args['set'],
            'where' => $args['where'],
            'data'  => empty($args['data']) ? [] : $args['data']
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function delete(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'folders',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getWithEntities(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);

        return DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['folders', 'entities_folders'],
            'left_join' => ['folders.id = entities_folders.folder_id'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'order_by'  => empty($args['orderBy']) ? [] : $args['orderBy']
        ]);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getWithEntitiesAndResources(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data']);

        return DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['folders', 'entities_folders', 'resources_folders'],
            'left_join' => ['folders.id = entities_folders.folder_id', 'folders.id = resources_folders.folder_id'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'groupBy'   => empty($args['groupBy']) ? [] : $args['groupBy'],
            'order_by'  => empty($args['orderBy']) ? [] : $args['orderBy'],
            'limit'     => empty($args['limit']) ? 0 : $args['limit'],
        ]);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getWithResources(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data']);

        return DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['folders', 'resources_folders'],
            'left_join' => ['folders.id = resources_folders.folder_id'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'groupBy'   => empty($args['groupBy']) ? [] : $args['groupBy']
        ]);
    }
}
