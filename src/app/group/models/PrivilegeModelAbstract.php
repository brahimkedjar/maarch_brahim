<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Privilege Model Abstract
 * @author dev@maarch.org
 */

namespace Group\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

abstract class PrivilegeModelAbstract
{
    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function get(array $args): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit', 'offset']);

        $privileges = DatabaseModel::select([
            'select'   => $args['select'] ?? ['*'],
            'table'    => ['usergroups_services'],
            'where'    => $args['where'] ?? [],
            'data'     => $args['data'] ?? [],
            'order_by' => $args['orderBy'] ?? [],
            'offset'   => $args['offset'] ?? 0,
            'limit'    => $args['limit'] ?? 0
        ]);

        return $privileges;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getByUser(array $args): array
    {
        ValidatorModel::notEmpty($args, ['id']);
        ValidatorModel::intVal($args, ['id']);

        return DatabaseModel::select([
            'select' => ['usergroups_services.service_id, usergroups_services.parameters'],
            'table'  => ['usergroup_content, usergroups_services, usergroups'],
            'where'  => [
                'usergroup_content.group_id = usergroups.id',
                'usergroups.group_id = usergroups_services.group_id',
                'usergroup_content.user_id = ?'
            ],
            'data'   => [$args['id']]
        ]);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getPrivilegesByGroupId(array $args): array
    {
        ValidatorModel::notEmpty($args, ['groupId']);
        ValidatorModel::intVal($args, ['groupId']);

        $privileges = DatabaseModel::select([
            'select' => ['service_id'],
            'table'  => ['usergroups_services, usergroups'],
            'where'  => ['usergroups_services.group_id = usergroups.group_id', 'usergroups.id = ?'],
            'data'   => [$args['groupId']]
        ]);

        return array_column($privileges, 'service_id');
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function addPrivilegeToGroup(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['privilegeId', 'groupId']);
        ValidatorModel::stringType($args, ['privilegeId', 'groupId']);

        DatabaseModel::insert([
            'table'         => 'usergroups_services',
            'columnsValues' => [
                'group_id'   => $args['groupId'],
                'service_id' => $args['privilegeId'],
            ]
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function removePrivilegeToGroup(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['privilegeId', 'groupId']);
        ValidatorModel::stringType($args, ['privilegeId', 'groupId']);

        DatabaseModel::delete([
            'table' => 'usergroups_services',
            'where' => ['group_id = ?', 'service_id = ?'],
            'data'  => [$args['groupId'], $args['privilegeId']]
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function groupHasPrivilege(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['groupId', 'privilegeId']);
        ValidatorModel::stringType($args, ['groupId', 'privilegeId']);

        $service = DatabaseModel::select([
            'select' => ['group_id', 'service_id'],
            'table'  => ['usergroups_services'],
            'where'  => ['group_id = ?', 'service_id = ?'],
            'data'   => [$args['groupId'], $args['privilegeId']]
        ]);

        return !empty($service);
    }

    /**
     * @param array $args
     * @return mixed|null
     * @throws Exception
     */
    public static function getParametersFromGroupPrivilege(array $args): mixed
    {
        ValidatorModel::notEmpty($args, ['groupId', 'privilegeId']);
        ValidatorModel::stringType($args, ['groupId', 'privilegeId']);

        $extra = DatabaseModel::select([
            'select' => ['parameters'],
            'table'  => ['usergroups_services'],
            'where'  => ['usergroups_services.group_id = ?', 'usergroups_services.service_id = ?'],
            'data'   => [$args['groupId'], $args['privilegeId']]
        ]);

        $extra = array_column($extra, 'parameters');

        if (empty($extra)) {
            return null;
        }

        return json_decode($extra[0], true);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getByUserAndPrivilege(array $args): array
    {
        ValidatorModel::notEmpty($args, ['userId', 'privilegeId']);
        ValidatorModel::intVal($args, ['userId']);
        ValidatorModel::stringType($args, ['privilegeId']);

        return DatabaseModel::select([
            'select' => ['usergroups_services.service_id, usergroups_services.parameters'],
            'table'  => ['usergroup_content, usergroups_services, usergroups'],
            'where'  => [
                'usergroup_content.group_id = usergroups.id',
                'usergroups.group_id = usergroups_services.group_id',
                'usergroup_content.user_id = ?',
                'usergroups_services.service_id = ?'
            ],
            'data'   => [$args['userId'], $args['privilegeId']]
        ]);
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function updateParameters(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['groupId', 'privilegeId']);
        ValidatorModel::stringType($args, ['groupId', 'privilegeId']);

        DatabaseModel::update([
            'table' => 'usergroups_services',
            'set'   => ['parameters ' => $args['parameters']],
            'where' => ['usergroups_services.group_id = ?', 'usergroups_services.service_id = ?'],
            'data'  => [$args['groupId'], $args['privilegeId']]
        ]);

        return true;
    }
}
