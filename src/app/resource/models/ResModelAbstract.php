<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Res Model
 * @author dev@maarch.org
 */

namespace Resource\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

abstract class ResModelAbstract
{
    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getOnView(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'table', 'leftJoin', 'where', 'data', 'orderBy', 'groupBy']);
        ValidatorModel::intType($aArgs, ['limit', 'offset']);

        return DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => array_merge(['res_view_letterbox'], $aArgs['table'] ?? []),
            'left_join' => empty($aArgs['leftJoin']) ? [] : $aArgs['leftJoin'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'groupBy'   => empty($aArgs['groupBy']) ? [] : $aArgs['groupBy'],
            'offset'    => empty($aArgs['offset']) ? 0 : $aArgs['offset'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function get(array $args): array
    {
        ValidatorModel::notEmpty($args, ['select']);
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy', 'groupBy']);
        ValidatorModel::intType($args, ['limit']);

        return DatabaseModel::select([
            'select'   => $args['select'],
            'table'    => ['res_letterbox'],
            'where'    => empty($args['where']) ? [] : $args['where'],
            'data'     => empty($args['data']) ? [] : $args['data'],
            'order_by' => empty($args['orderBy']) ? [] : $args['orderBy'],
            'limit'    => empty($args['limit']) ? 0 : $args['limit'],
            'groupBy'  => empty($args['groupBy']) ? [] : $args['groupBy'],
        ]);
    }

    /**
     * @param array $args
     * @return array|mixed
     * @throws Exception
     */
    public static function getById(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);

        $resource = DatabaseModel::select([
            'select' => $args['select'],
            'table'  => ['res_letterbox'],
            'where'  => ['res_id = ?'],
            'data'   => [$args['resId']]
        ]);

        if (empty($resource[0])) {
            return [];
        }

        return $resource[0];
    }

    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function create(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['res_id', 'model_id', 'category_id', 'typist', 'creation_date']);
        ValidatorModel::stringType($args, [
            'category_id',
            'creation_date',
            'format',
            'docserver_id',
            'path',
            'filename',
            'fingerprint'
        ]);
        ValidatorModel::intVal($args, ['res_id', 'model_id', 'typist', 'filesize']);

        DatabaseModel::insert([
            'table'         => 'res_letterbox',
            'columnsValues' => $args
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function update(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['set', 'postSet', 'where', 'data']);

        DatabaseModel::update([
            'table'   => 'res_letterbox',
            'set'     => $args['set'] ?? null,
            'postSet' => $args['postSet'] ?? null,
            'where'   => $args['where'],
            'data'    => $args['data']
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function delete(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'res_letterbox',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function getLastResources(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['limit', 'select']);
        ValidatorModel::intType($aArgs, ['limit', 'userId',]);
        ValidatorModel::arrayType($aArgs, ['select']);

        return DatabaseModel::select([
            'select'   => $aArgs['select'],
            'table'    => ['history, res_letterbox, status'],
            'where'    => [
                'history.user_id = ?',
                'history.table_name IN (?)',
                'history.record_id IS NOT NULL',
                'history.record_id != ?',
                'history.event_id != ?',
                'history.event_id NOT LIKE ?',
                'CAST(history.record_id AS INT) = res_letterbox.res_id',
                'res_letterbox.status != ?',
                'res_letterbox.status = status.id'
            ],
            'data'     => [
                $aArgs['userId'],
                ['res_letterbox', 'res_view_letterbox'],
                'none',
                'linkup',
                'attach%',
                'DEL'
            ],
            'groupBy'  => [
                'res_letterbox.type_id',
                'res_letterbox.creation_date',
                'res_letterbox.res_id',
                'res_letterbox.subject',
                'res_letterbox.status',
                'res_letterbox.category_id'
            ],
            'order_by' => ['MAX(history.event_date) DESC'],
            'limit'    => $aArgs['limit']
        ]);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getByAltIdentifier(array $args): array
    {
        ValidatorModel::notEmpty($args, ['altIdentifier']);
        ValidatorModel::stringType($args, ['altIdentifier']);

        $resource = DatabaseModel::select([
            'select' => empty($args['select']) ? ['*'] : $args['select'],
            'table'  => ['res_letterbox'],
            'where'  => ['alt_identifier = ?'],
            'data'   => [$args['altIdentifier']]
        ]);

        if (empty($resource[0])) {
            return [];
        }

        return $resource[0];
    }

    /**
     * @return array[]
     */
    public static function getCategories(): array
    {
        return [
            [
                'id'    => 'incoming',
                'label' => _INCOMING
            ],
            [
                'id'    => 'outgoing',
                'label' => _OUTGOING
            ],
            [
                'id'    => 'internal',
                'label' => _INTERNAL
            ],
            [
                'id'    => 'ged_doc',
                'label' => _GED_DOC
            ],
            [
                'id'    => 'registeredMail',
                'label' => _REGISTERED_MAIL
            ]
        ];
    }

    /**
     * @param array $args
     * @return string
     * @throws Exception
     */
    public static function getCategoryLabel(array $args): string
    {
        ValidatorModel::stringType($args, ['categoryId']);

        $categories = ResModel::getCategories();
        foreach ($categories as $category) {
            if ($category['id'] == $args['categoryId']) {
                return $category['label'];
            }
        }

        return '';
    }

    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function removeExternalLink(array $args): bool
    {
        ValidatorModel::intType($args, ['resId']);
        ValidatorModel::stringType($args, ['externalId']);

        DatabaseModel::update([
            'table'   => 'res_letterbox',
            'postSet' => ['external_id' => "external_id - 'signatureBookId'", 'external_state' => "'{}'::jsonb"],
            'where'   => ['res_id = ?', "external_id->>'signatureBookId' = ?"],
            'data'    => [$args['resId'], $args['externalId']]
        ]);

        return true;
    }
}
