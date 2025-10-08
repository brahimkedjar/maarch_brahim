<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Model
 * @author dev@maarch.org
 */

namespace Group\models;

use Exception;
use Group\controllers\PrivilegeController;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;
use User\controllers\UserController;
use User\models\UserModel;

abstract class GroupModelAbstract
{
    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function get(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit']);

        return DatabaseModel::select([
            'select'   => empty($args['select']) ? ['*'] : $args['select'],
            'table'    => ['usergroups'],
            'where'    => $args['where'] ?? [],
            'data'     => $args['data'] ?? [],
            'order_by' => $args['orderBy'] ?? [],
            'limit'    => $args['limit'] ?? 0
        ]);
    }

    /**
     * @param array $aArgs
     * @return mixed
     * @throws Exception
     */
    public static function getById(array $aArgs): mixed
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        $aGroups = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['usergroups'],
            'where'  => ['id = ?'],
            'data'   => [$aArgs['id']]
        ]);

        return $aGroups[0];
    }

    /**
     * @param array $aArgs
     * @return mixed
     * @throws Exception
     */
    public static function getByGroupId(array $aArgs): mixed
    {
        ValidatorModel::notEmpty($aArgs, ['groupId']);
        ValidatorModel::stringType($aArgs, ['groupId']);

        $aGroups = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['usergroups'],
            'where'  => ['group_id = ?'],
            'data'   => [$aArgs['groupId']]
        ]);

        return $aGroups[0] ?? null;
    }

    /**
     * @param array $aArgs
     * @return true
     * @throws Exception
     */
    public static function create(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['groupId', 'description', 'clause']);
        ValidatorModel::stringType($aArgs, ['groupId', 'description', 'clause', 'comment']);

        DatabaseModel::insert([
            'table'         => 'usergroups',
            'columnsValues' => [
                'group_id'    => $aArgs['groupId'],
                'group_desc'  => $aArgs['description'],
                'external_id' => $aArgs['external_id'] ?? '{}',
            ]
        ]);

        DatabaseModel::insert([
            'table'         => 'security',
            'columnsValues' => [
                'group_id'       => $aArgs['groupId'],
                'coll_id'        => 'letterbox_coll',
                'where_clause'   => $aArgs['clause'],
                'maarch_comment' => $aArgs['comment'],
            ]
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function update(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where']);
        ValidatorModel::arrayType($args, ['set', 'postSet', 'where', 'data']);

        DatabaseModel::update([
            'table'   => 'usergroups',
            'set'     => empty($args['set']) ? [] : $args['set'],
            'postSet' => empty($args['postSet']) ? [] : $args['postSet'],
            'where'   => $args['where'],
            'data'    => empty($args['data']) ? [] : $args['data']
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function updateSecurity(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['set', 'where', 'data']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'security',
            'set'   => $args['set'],
            'where' => $args['where'],
            'data'  => $args['data']
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

        $group = GroupModel::getById(['id' => $aArgs['id'], 'select' => ['group_id']]);

        DatabaseModel::delete([
            'table' => 'usergroups',
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);
        DatabaseModel::delete([
            'table' => 'usergroup_content',
            'where' => ['group_id = ?'],
            'data'  => [$aArgs['id']]
        ]);
        DatabaseModel::delete([
            'table' => 'usergroups_services',
            'where' => ['group_id = ?'],
            'data'  => [$group['group_id']]
        ]);
        DatabaseModel::delete([
            'table' => 'security',
            'where' => ['group_id = ?'],
            'data'  => [$group['group_id']]
        ]);
        DatabaseModel::delete([
            'table' => 'groupbasket',
            'where' => ['group_id = ?'],
            'data'  => [$group['group_id']]
        ]);
        DatabaseModel::delete([
            'table' => 'groupbasket_redirect',
            'where' => ['group_id = ?'],
            'data'  => [$group['group_id']]
        ]);
        DatabaseModel::delete([
            'table' => 'users_baskets_preferences',
            'where' => ['group_serial_id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getUsersById(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::arrayType($aArgs, ['select']);

        return DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['usergroup_content, users'],
            'where'  => ['group_id = ?', 'usergroup_content.user_id = users.id', 'users.status != ?'],
            'data'   => [$aArgs['id'], 'DEL']
        ]);
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getAvailableGroupsByUserId(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['userId', 'administratorId']);
        ValidatorModel::intVal($aArgs, ['userId', 'administratorId']);

        $rawUserGroups = UserModel::getGroupsById(['id' => $aArgs['userId']]);
        $userGroups = array_column($rawUserGroups, 'group_id');

        $allGroups = GroupModel::get(['select' => ['group_id', 'group_desc'], 'orderBy' => ['group_desc']]);

        if (UserController::isRoot(['id' => $aArgs['administratorId']])) {
            $assignableGroups = GroupModel::get(['select' => ['group_id'], 'orderBy' => ['group_desc']]);
        } else {
            $assignableGroups = PrivilegeController::getAssignableGroups(['userId' => $aArgs['administratorId']]);
        }
        $assignableGroups = array_column($assignableGroups, 'group_id');

        foreach ($allGroups as $key => $value) {
            if (in_array($value['group_id'], $assignableGroups)) {
                $allGroups[$key]['enabled'] = true;
            } else {
                $allGroups[$key]['enabled'] = false;
            }

            if (in_array($value['group_id'], $userGroups)) {
                $allGroups[$key]['checked'] = true;
            } else {
                $allGroups[$key]['checked'] = false;
            }
        }

        return $allGroups;
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getGroupWithUsersGroups(array $aArgs = []): array
    {
        ValidatorModel::notEmpty($aArgs, ['userId', 'groupId']);
        ValidatorModel::intVal($aArgs, ['userId', 'groupId']);

        return DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['usergroup_content, usergroups'],
            'where'  => [
                'usergroup_content.group_id = usergroups.id',
                'usergroup_content.user_id = ?',
                'usergroup_content.group_id = ?'
            ],
            'data'   => [$aArgs['userId'], $aArgs['groupId']]
        ]);
    }

    /**
     * @param array $aArgs
     * @return mixed
     * @throws Exception
     */
    public static function getSecurityByGroupId(array $aArgs = []): mixed
    {
        ValidatorModel::notEmpty($aArgs, ['groupId']);
        ValidatorModel::stringType($aArgs, ['groupId']);

        $aData = DatabaseModel::select([
            'select' => ['where_clause', 'maarch_comment'],
            'table'  => ['security'],
            'where'  => ['group_id = ?'],
            'data'   => [$aArgs['groupId']]
        ]);

        return $aData[0];
    }
}
