<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   Action Model Abstract
 * @author  dev@maarch.org
 */

namespace Action\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

abstract class ActionModelAbstract
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
            'select'   => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'    => ['actions'],
            'where'    => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'     => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by' => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'limit'    => empty($aArgs['limit']) ? 0 : $aArgs['limit']
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

        $action = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['actions'],
            'where'  => ['id = ?'],
            'data'   => [$aArgs['id']]
        ]);

        if (empty($action[0])) {
            return [];
        }

        return $action[0];
    }

    /**
     * @param array $aArgs
     * @return int
     * @throws Exception
     */
    public static function create(array $aArgs): int
    {
        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'actions_id_seq']);

        unset($aArgs['actionCategories']);
        $aArgs['id'] = $nextSequenceId;
        DatabaseModel::insert([
            'table'         => 'actions',
            'columnsValues' => $aArgs
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
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::update([
            'table'   => 'actions',
            'set'     => !empty($args['set']) ? $args['set'] : [],
            'postSet' => !empty($args['postSet']) ? $args['postSet'] : [],
            'where'   => $args['where'],
            'data'    => $args['data'],
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
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        DatabaseModel::delete([
            'table' => 'actions',
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);
        DatabaseModel::delete([
            'table' => 'actions_groupbaskets',
            'where' => ['id_action = ?'],
            'data'  => [$aArgs['id']]
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getCategoriesById(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        return DatabaseModel::select([
            'select' => ['category_id'],
            'table'  => ['actions_categories'],
            'where'  => ['action_id = ?'],
            'data'   => [$aArgs['id']]
        ]);
    }

    /**
     * @param array $aArgs
     * @return true
     * @throws Exception
     */
    public static function createCategories(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'categories']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::arrayType($aArgs, ['categories']);

        foreach ($aArgs['categories'] as $category) {
            DatabaseModel::insert([
                'table'         => 'actions_categories',
                'columnsValues' => [
                    'action_id'   => $aArgs['id'],
                    'category_id' => $category,
                ]
            ]);
        }

        return true;
    }

    /**
     * @param array $aArgs
     * @return true
     * @throws Exception
     */
    public static function deleteCategories(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        DatabaseModel::delete([
            'table' => 'actions_categories',
            'where' => ['action_id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        return true;
    }

    /**
     * @return array
     */
    public static function getKeywords(): array
    {
        $tabKeyword = [];
        $tabKeyword[] = ['value' => '', 'label' => _NO_KEYWORD];
        $tabKeyword[] = ['value' => 'redirect', 'label' => _REDIRECTION, 'desc' => _KEYWORD_REDIRECT_DESC];

        return $tabKeyword;
    }

    /**
     * @param array $aArgs
     * @return mixed|string
     * @throws Exception
     */
    public static function getActionPageById(array $aArgs): mixed
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        $action = DatabaseModel::select([
            'select' => ['action_page'],
            'table'  => ['actions'],
            'where'  => ['id = ? AND enabled = ?'],
            'data'   => [$aArgs['id'], 'Y']
        ]);

        if (empty($action[0])) {
            return '';
        }

        return $action[0]['action_page'];
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getForBasketPage(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['basketId', 'groupId']);
        ValidatorModel::stringType($aArgs, ['basketId', 'groupId']);

        return DatabaseModel::select([
            'select'   => ['id_action', 'where_clause', 'default_action_list', 'actions.label_action'],
            'table'    => ['actions_groupbaskets, actions'],
            'where'    => [
                'basket_id = ?',
                'group_id = ?',
                'used_in_action_page = ?',
                'actions_groupbaskets.id_action = actions.id'
            ],
            'data'     => [$aArgs['basketId'], $aArgs['groupId'], 'Y'],
            'order_by' => ['default_action_list DESC']
        ]);
    }
}
