<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Indexing Models Entities
 * @author dev@maarch.org
 */

namespace IndexingModel\models;

use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class IndexingModelsEntitiesModel
{
    public const ALL_ENTITIES = 'ALL_ENTITIES';

    public static function create(array $args)
    {
        ValidatorModel::notEmpty($args, ['model_id']);
        ValidatorModel::stringType($args, ['entity_id', 'keyword']);
        ValidatorModel::intVal($args, ['model_id']);


        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'indexing_models_entities_id_seq']);

        DatabaseModel::insert([
            'table'         => 'indexing_models_entities',
            'columnsValues' => [
                'id'        => $nextSequenceId,
                'model_id'  => $args['model_id'],
                'entity_id' => $args['entity_id'] ?? null,
                'keyword'   => $args['keyword'] ?? null
            ]
        ]);

        return $nextSequenceId;
    }

    public static function get(array $args = [])
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit']);

        $models = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['indexing_models_entities'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'order_by'  => empty($args['orderBy']) ? [] : $args['orderBy'],
            'limit'     => empty($args['limit']) ? 0 : $args['limit']
        ]);

        return $models;
    }

    public static function getById(array $args)
    {
        ValidatorModel::notEmpty($args, ['id']);
        ValidatorModel::intVal($args, ['id']);
        ValidatorModel::arrayType($args, ['select']);

        $model = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['indexing_models_entities'],
            'where'     => ['id = ?'],
            'data'      => [$args['id']],
        ]);

        if (empty($model[0])) {
            return [];
        }

        return $model[0];
    }

    public static function getByModelId(array $args)
    {
        ValidatorModel::notEmpty($args, ['model_id']);
        ValidatorModel::intVal($args, ['model_id']);
        ValidatorModel::arrayType($args, ['select']);

        $model = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['indexing_models_entities'],
            'where'     => ['model_id = ?'],
            'data'      => [$args['model_id']],
        ]);

        return $model;
    }

    public static function getByEntityId(array $args)
    {
        ValidatorModel::notEmpty($args, ['entity_id']);
        ValidatorModel::stringType($args, ['entity_id']);
        ValidatorModel::arrayType($args, ['select']);

        $model = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['indexing_models_entities'],
            'where'     => ['entity_id = ?'],
            'data'      => [$args['entity_id']],
        ]);

        return $model;
    }

    public static function getByEntityIdOrKeyword(array $args)
    {
        ValidatorModel::notEmpty($args, ['entity_id', 'keyword']);
        ValidatorModel::stringType($args, ['entity_id', 'keyword']);
        ValidatorModel::arrayType($args, ['select']);

        $model = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['indexing_models_entities'],
            'where'     => ['entity_id = ? OR keyword = ?'],
            'data'      => [$args['entity_id'], $args['keyword']],
        ]);

        return $model;
    }

    public static function getModelIdsFromEntityWithKeyword(array $args)
    {
        ValidatorModel::notEmpty($args, ['entity_id', 'keyword']);
        ValidatorModel::stringType($args, ['entity_id', 'keyword']);

        $model = DatabaseModel::select([
            'select'    => ['distinct(IM.id)'],
            'table'     => ['indexing_models_entities as IME', 'indexing_models as IM'],
            'left_join' => [
                'IME.model_id = IM.id AND IM.private = false and IM.enabled = true and IM.id in '
                . '(select model_id from indexing_models_entities where entity_id = ?  OR keyword = ?)'
            ],
            'where'     => ['IM.id IS NOT NULL'],
            'data'      => [$args['entity_id'], $args['keyword']]
        ]);

        return $model;
    }

    public static function update(array $args)
    {
        ValidatorModel::notEmpty($args, ['set', 'where', 'data']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'indexing_models_entities',
            'set'   => $args['set'],
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }

    public static function delete(array $args)
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'indexing_models_entities',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
