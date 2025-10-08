<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Prepared Clause Controller
 * @author dev@maarch.org
 */

namespace SrcCore\controllers;

use Exception;
use SrcCore\models\ValidatorModel;
use Entity\models\EntityModel;
use Resource\models\ResModel;
use User\models\UserModel;

class PreparedClauseController
{
    /**
     * @param array $aArgs
     * @return string
     * @throws Exception
     */
    public static function getPreparedClause(array $aArgs): string
    {
        ValidatorModel::notEmpty($aArgs, ['clause', 'userId']);
        ValidatorModel::stringType($aArgs, ['clause']);
        ValidatorModel::intVal($aArgs, ['userId']);

        $clause = $aArgs['clause'];
        $user = UserModel::getById(['id' => $aArgs['userId'], 'select' => ['user_id', 'mail']]);

        if (str_contains($clause, '@user_id')) {
            $clause = str_replace('@user_id', "{$aArgs['userId']}", $clause);
        }
        if (str_contains($clause, '@user')) {
            $clause = str_replace('@user', "'{$user['user_id']}'", $clause);
        }
        if (str_contains($clause, '@email')) {
            $clause = str_replace('@email', "'{$user['mail']}'", $clause);
        }
        if (str_contains($clause, '@my_entities_id')) {
            $entities = EntityModel::getByUserId(['userId' => $aArgs['userId'], 'select' => ['entity_id']]);
            $entities = array_column($entities, 'entity_id');
            if (!empty($entities)) {
                $entities = EntityModel::get(
                    [
                        'select' => ['id'],
                        'where'  => ['entity_id in (?)'],
                        'data'   => [$entities]
                    ]
                );
            }

            $myEntitiesClause = '';
            foreach ($entities as $key => $entity) {
                if ($key > 0) {
                    $myEntitiesClause .= ", ";
                }
                $myEntitiesClause .= $entity['id'];
            }
            if (empty($myEntitiesClause)) {
                $myEntitiesClause = 0;
            }

            $clause = str_replace('@my_entities_id', $myEntitiesClause, $clause);
        }
        if (str_contains($clause, '@my_entities')) {
            $entities = EntityModel::getByUserId(['userId' => $aArgs['userId'], 'select' => ['entity_id']]);

            $myEntitiesClause = '';
            foreach ($entities as $key => $entity) {
                if ($key > 0) {
                    $myEntitiesClause .= ", ";
                }
                $myEntitiesClause .= "'{$entity['entity_id']}'";
            }
            if (empty($myEntitiesClause)) {
                $myEntitiesClause = "''";
            }

            $clause = str_replace('@my_entities', $myEntitiesClause, $clause);
        }
        if (str_contains($clause, '@my_primary_entity_id')) {
            $entity = UserModel::getPrimaryEntityById(['id' => $aArgs['userId'], 'select' => ['entities.id']]);

            if (empty($entity)) {
                $primaryEntity = 0;
            } else {
                $primaryEntity = $entity['id'];
            }

            $clause = str_replace('@my_primary_entity_id', $primaryEntity, $clause);
        }
        if (str_contains($clause, '@my_primary_entity')) {
            $entity = UserModel::getPrimaryEntityById(['id' => $aArgs['userId'], 'select' => ['entities.entity_id']]);

            if (empty($entity)) {
                $primaryEntity = "''";
            } else {
                $primaryEntity = "'" . $entity['entity_id'] . "'";
            }

            $clause = str_replace('@my_primary_entity', $primaryEntity, $clause);
        }
        if (str_contains($clause, '@all_entities')) {
            $allEntities = EntityModel::get(['select' => ['entity_id'], 'where' => ['enabled = ?'], 'data' => ['Y']]);

            $allEntitiesClause = '';
            foreach ($allEntities as $key => $allEntity) {
                if ($key > 0) {
                    $allEntitiesClause .= ", ";
                }
                $allEntitiesClause .= "'{$allEntity['entity_id']}'";
            }
            if (empty($allEntitiesClause)) {
                $allEntitiesClause = "''";
            }

            $clause = str_replace("@all_entities", $allEntitiesClause, $clause);
        }

        $total = preg_match_all(
            "|@subentities_id\[([^\]]*)\]|",
            $clause,
            $subEntities,
            PREG_PATTERN_ORDER
        );
        if ($total > 0) {
            for ($i = 0; $i < $total; $i++) {
                $aEntities = [];
                $tmpSubEntities = str_replace("'", '', $subEntities[1][$i]);
                if (str_contains($tmpSubEntities, ',')) {
                    $aEntities = explode(',', $tmpSubEntities);
                } else {
                    $aEntities[] = $tmpSubEntities;
                }

                $allSubEntities = [];
                foreach ($aEntities as $entity) {
                    if (!empty($entity)) {
                        $subEntitiesForEntity = EntityModel::getEntityChildrenById(['id' => trim($entity)]);
                        unset($subEntitiesForEntity[0]);
                        $allSubEntities = array_merge($allSubEntities, $subEntitiesForEntity);
                    }
                }

                $allSubEntitiesClause = '';
                foreach ($allSubEntities as $key => $allSubEntity) {
                    if ($key > 0) {
                        $allSubEntitiesClause .= ", ";
                    }
                    $allSubEntitiesClause .= "'{$allSubEntity}'";
                }
                if (empty($allSubEntitiesClause)) {
                    $allSubEntitiesClause = "0";
                }

                $clause = preg_replace("|@subentities_id\[[^\]]*\]|", $allSubEntitiesClause, $clause, 1);
            }
        }

        $total = preg_match_all(
            "|@subentities\[('[^\]]*')\]|",
            $clause,
            $subEntities,
            PREG_PATTERN_ORDER
        );
        if ($total > 0) {
            for ($i = 0; $i < $total; $i++) {
                $aEntities = [];
                $tmpSubEntities = str_replace("'", '', $subEntities[1][$i]);
                if (str_contains($tmpSubEntities, ',')) {
                    $aEntities = explode(',', $tmpSubEntities);
                } else {
                    $aEntities[] = $tmpSubEntities;
                }

                $allSubEntities = [];
                foreach ($aEntities as $entity) {
                    if (!empty($entity)) {
                        $subEntitiesForEntity = EntityModel::getEntityChildren(['entityId' => trim($entity)]);
                        unset($subEntitiesForEntity[0]);
                        $allSubEntities = array_merge($allSubEntities, $subEntitiesForEntity);
                    }
                }

                $allSubEntitiesClause = '';
                foreach ($allSubEntities as $key => $allSubEntity) {
                    if ($key > 0) {
                        $allSubEntitiesClause .= ", ";
                    }
                    $allSubEntitiesClause .= "'{$allSubEntity}'";
                }
                if (empty($allSubEntitiesClause)) {
                    $allSubEntitiesClause = "''";
                }

                $clause = preg_replace("|@subentities\['[^\]]*'\]|", $allSubEntitiesClause, $clause, 1);
            }
        }

        $total = preg_match_all(
            "|@immediate_children\[('[^\]]*')\]|",
            $clause,
            $immediateChildren,
            PREG_PATTERN_ORDER
        );
        if ($total > 0) {
            for ($i = 0; $i < $total; $i++) {
                $aEntities = [];
                $tmpImmediateChildren = str_replace("'", '', $immediateChildren[1][$i]);
                if (str_contains($tmpImmediateChildren, ',')) {
                    $aEntities = explode(',', $tmpImmediateChildren);
                } else {
                    $aEntities[] = $tmpImmediateChildren;
                }

                $allImmediateChildren = [];
                foreach ($aEntities as $entity) {
                    $immediateChildrenForEntity = EntityModel::get(
                        [
                            'select' => ['entity_id'],
                            'where'  => ['parent_entity_id = ?'],
                            'data'   => [trim($entity)]
                        ]
                    );
                    foreach ($immediateChildrenForEntity as $value) {
                        $allImmediateChildren[] = $value['entity_id'];
                    }
                }

                $allImmediateChildrenClause = '';
                foreach ($allImmediateChildren as $key => $allImmediateChild) {
                    if ($key > 0) {
                        $allImmediateChildrenClause .= ", ";
                    }
                    $allImmediateChildrenClause .= "'{$allImmediateChild}'";
                }
                if (empty($allImmediateChildrenClause)) {
                    $allImmediateChildrenClause = "''";
                }

                $clause = preg_replace(
                    "|@immediate_children\['[^\]]*'\]|",
                    $allImmediateChildrenClause,
                    $clause,
                    1
                );
            }
        }

        $total = preg_match_all(
            "|@parent_entity\[('[^\]]*')\]|",
            $clause,
            $parentEntity,
            PREG_PATTERN_ORDER
        );
        if ($total > 0) {
            for ($i = 0; $i < $total; $i++) {
                $tmpParentEntity = trim(str_replace("'", '', $parentEntity[1][$i]));
                if (!empty($tmpParentEntity)) {
                    $entity = EntityModel::getByEntityId(
                        [
                            'entityId' => $tmpParentEntity,
                            'select'   => ['entity_id', 'parent_entity_id']
                        ]
                    );
                }
                if (empty($entity['parent_entity_id'])) {
                    $parentEntityClause = "''";
                } else {
                    $parentEntityClause = "'{$entity['parent_entity_id']}'";
                }

                $clause = preg_replace("|@parent_entity\['[^\]]*'\]|", $parentEntityClause, $clause, 1);
            }
        }

        $total = preg_match_all(
            "|@sisters_entities\[('[^\]]*')\]|",
            $clause,
            $sistersEntities,
            PREG_PATTERN_ORDER
        );
        if ($total > 0) {
            for ($i = 0; $i < $total; $i++) {
                $tmpSisterEntity = trim(str_replace("'", '', $sistersEntities[1][$i]));
                if (!empty($tmpSisterEntity)) {
                    $sisterEntity = EntityModel::getByEntityId(
                        [
                            'entityId' => $tmpSisterEntity,
                            'select'   => ['parent_entity_id']
                        ]
                    );
                }
                $allSisterEntities = [];
                if (!empty($sisterEntity)) {
                    $allSisterEntities = EntityModel::get(
                        [
                            'select' => ['entity_id'],
                            'where'  => ['parent_entity_id = ?'],
                            'data'   => [
                                $sisterEntity['parent_entity_id']
                            ]
                        ]
                    );
                }

                $allSisterEntitiesClause = '';
                foreach ($allSisterEntities as $key => $allSisterEntity) {
                    if ($key > 0) {
                        $allSisterEntitiesClause .= ", ";
                    }
                    $allSisterEntitiesClause .= "'{$allSisterEntity['entity_id']}'";
                }
                if (empty($allSisterEntitiesClause)) {
                    $allSisterEntitiesClause = "''";
                }

                $clause = preg_replace(
                    "|@sisters_entities\['[^\]]*'\]|",
                    $allSisterEntitiesClause,
                    $clause,
                    1
                );
            }
        }

        $total = preg_match_all(
            "|@entity_type\[('[^\]]*')\]|",
            $clause,
            $entityType,
            PREG_PATTERN_ORDER
        );
        if ($total > 0) {
            for ($i = 0; $i < $total; $i++) {
                $tmpEntityType = trim(str_replace("'", '', $entityType[1][$i]));
                $allEntitiesType = EntityModel::get(
                    [
                        'select' => ['entity_id'],
                        'where'  => ['entity_type = ?'],
                        'data'   => [$tmpEntityType]
                    ]
                );

                $allEntitiesTypeClause = '';
                foreach ($allEntitiesType as $key => $allEntityType) {
                    if ($key > 0) {
                        $allEntitiesTypeClause .= ", ";
                    }
                    $allEntitiesTypeClause .= "'{$allEntityType['entity_id']}'";
                }
                if (empty($allEntitiesTypeClause)) {
                    $allEntitiesTypeClause = "''";
                }

                $clause = preg_replace("|@entity_type\['[^\]]*'\]|", $allEntitiesTypeClause, $clause, 1);
            }
        }

        return "({$clause})";
    }

    /**
     * @param array $aArgs
     * @return bool
     * @throws Exception
     */
    public static function isRequestValid(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['clause', 'userId']);
        ValidatorModel::stringType($aArgs, ['clause', 'userId']);
        ValidatorModel::arrayType($aArgs, ['select', 'orderBy']);
        ValidatorModel::intType($aArgs, ['limit']);

        $user = UserModel::getByLogin(['login' => $aArgs['userId'], 'select' => ['id']]);
        $clause = PreparedClauseController::getPreparedClause(['clause' => $aArgs['clause'], 'userId' => $user['id']]);

        $preg = preg_match(
            '#\b(?:abort|alter|copy|create|delete|disgard|
drop|execute|grant|insert|load|lock|move|reset|truncate|update)\b#i',
            $clause
        );
        if ($preg === 1) {
            return false;
        }

        if (!empty($aArgs['select'])) {
            $select = implode(" AND ", $aArgs['select']);
            $preg = preg_match(
                '#\b(?:abort|alter|copy|create|delete|disgard|
drop|execute|grant|insert|load|lock|move|reset|truncate|update|select)\b#i',
                $select
            );
            if ($preg === 1) {
                return false;
            }
        } else {
            $aArgs['select'] = [1];
        }

        try {
            ResModel::getOnView(
                [
                    'select'  => $aArgs['select'],
                    'where'   => [$clause, '1=1'],
                    'orderBy' => $aArgs['orderBy'] ?? [],
                    'limit'   => $aArgs['limit'] ?? null
                ]
            );
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
