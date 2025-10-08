<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Controller
 * @author dev@maarch.org
 */

namespace Group\controllers;

use Action\models\ActionModel;
use Basket\models\GroupBasketModel;
use Entity\models\EntityModel;
use Exception;
use Group\models\PrivilegeModel;
use Group\models\GroupModel;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\Group\Domain\Group;
use MaarchCourrier\SignatureBook\Domain\Problem\GroupCreateInSignatureBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\GroupUpdateInSignatureBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Infrastructure\Factory\CreateAndUpdateGroupInSignatoryBookFactory;
use MaarchCourrier\SignatureBook\Infrastructure\Factory\DeleteGroupInSignatoryBookFactory;
use Respect\Validation\Validator;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\ValidatorModel;
use User\controllers\UserController;
use User\models\UserEntityModel;
use User\models\UserGroupModel;
use User\models\UserModel;

class GroupController
{
    public const INDEXING_ACTIONS = [
        'confirmAction',
        'noConfirmAction',
        'closeMailAction',
        'sendSignatureBookAction',
        'closeAndIndexAction',
        'saveRegisteredMailAction',
        'saveAndPrintRegisteredMailAction',
        'saveAndIndexRegisteredMailAction'
    ];

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function get(Request $request, Response $response): Response
    {
        $hasPrivilege = PrivilegeController::hasPrivilege(
            ['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']]
        );

        $select = $hasPrivilege ? ['*'] : ['id', 'group_desc'];
        $groups = GroupModel::get(['select' => $select, 'orderBy' => ['group_desc']]);

        if ($hasPrivilege) {
            foreach ($groups as $key => $value) {
                $groups[$key]['users'] = GroupModel::getUsersById([
                    'id'     => $value['id'],
                    'select' => ['users.user_id', 'users.firstname', 'users.lastname']
                ]);
            }
        }

        return $response->withJson(['groups' => $groups]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $aArgs
     * @return Response
     * @throws Exception
     */
    public function getById(Request $request, Response $response, array $aArgs): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $group = GroupModel::getById(['id' => $aArgs['id']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        return $response->withJson(['group' => $group]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GroupCreateInSignatureBookFailedProblem
     * @throws GroupUpdateInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     * @throws Exception
     */
    public function create(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParsedBody();

        $check = Validator::stringType()->notEmpty()->validate($data['group_id']) &&
            preg_match("/^[\w-]*$/", $data['group_id']) && (strlen($data['group_id']) < 33);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['group_desc']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['security']['where_clause']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $existingGroup = GroupModel::getByGroupId(['groupId' => $data['group_id'], 'select' => ['1']]);
        if (!empty($existingGroup)) {
            return $response->withStatus(400)->withJson(['errors' => _ID . ' ' . _ALREADY_EXISTS]);
        }

        if (
            !PreparedClauseController::isRequestValid(
                ['clause' => $data['security']['where_clause'], 'userId' => $GLOBALS['login'], 'limit' => 1]
            )
        ) {
            return $response->withStatus(400)->withJson(['errors' => _INVALID_CLAUSE]);
        }

        $env = new Environment();
        $externalId = null;

        if ($env->isNewInternalParapheurEnabled()) {
            $group = (new Group())
                ->setLabel($data['group_desc'])
                ->setExternalId(null);

            $createGroupFactory = new CreateAndUpdateGroupInSignatoryBookFactory();
            $createGroup = $createGroupFactory->create();
            $group = $createGroup->createAndUpdateGroup($group);
            $externalId = [
                'external_id ' => json_encode($group->getExternalId()),
            ];
        }

        GroupModel::create(
            [
                'groupId'     => $data['group_id'],
                'description' => $data['group_desc'],
                'clause'      => $data['security']['where_clause'],
                'comment'     => $data['security']['maarch_comment'] ?? null,
                'external_id' => $externalId['external_id '] ?? '{}'
            ]
        );

        $group = GroupModel::getByGroupId(['groupId' => $data['group_id'], 'select' => ['id']]);
        if (empty($group)) {
            return $response->withStatus(500)->withJson(['errors' => 'Group Creation Error']);
        }

        return $response->withJson(['group' => $group['id']]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $aArgs
     * @return Response
     * @throws GroupCreateInSignatureBookFailedProblem
     * @throws GroupUpdateInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     * @throws Exception
     */
    public function update(Request $request, Response $response, array $aArgs): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $group = GroupModel::getById(['id' => $aArgs['id'], 'select' => ['external_id', 'group_id']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        $data = $request->getParsedBody();
        $check = Validator::stringType()->notEmpty()->validate($data['description']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['security']['where_clause']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if (
            !PreparedClauseController::isRequestValid(
                ['clause' => $data['security']['where_clause'], 'userId' => $GLOBALS['login'], 'limit' => 1]
            )
        ) {
            return $response->withStatus(400)->withJson(['errors' => _INVALID_CLAUSE]);
        }

        $env = new Environment();
        $externalId = json_decode($group['external_id'], true);

        if ($env->isNewInternalParapheurEnabled()) {
            $groupToUpdate = (new Group())
                ->setLabel($data['description'])
                ->setExternalId($externalId);

            $createGroupFactory = new CreateAndUpdateGroupInSignatoryBookFactory();
            $createGroup = $createGroupFactory->create();
            $createGroup->createAndUpdateGroup($groupToUpdate);
            $externalId = $groupToUpdate->getExternalId();
        }

        GroupModel::update([
            'set'   => ['group_desc' => $data['description'], 'external_id' => json_encode($externalId)],
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);
        GroupModel::updateSecurity([
            'set'   => [
                'where_clause'   => $data['security']['where_clause'],
                'maarch_comment' => $data['security']['maarch_comment']
            ],
            'where' => ['group_id = ?'],
            'data'  => [$group['group_id']]
        ]);

        return $response->withJson(['success' => 'success']);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $aArgs
     * @return Response
     * @throws Exception
     */
    public function delete(Request $request, Response $response, array $aArgs): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $group = GroupModel::getById(['id' => $aArgs['id']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }
        $env = new Environment();
        $externalId = json_decode($group['external_id'], true);

        if ($env->isNewInternalParapheurEnabled()) {
            $groupToDelete = (new Group())
                ->setExternalId($externalId);

            $deleteGroupFactory = new DeleteGroupInSignatoryBookFactory();
            $deleteGroup = $deleteGroupFactory->create();
            $deleteGroup->deleteGroup($groupToDelete);
        }

        GroupModel::delete(['id' => $aArgs['id']]);

        $groups = GroupModel::get(['orderBy' => ['group_desc']]);
        foreach ($groups as $key => $value) {
            $groups[$key]['users'] = GroupModel::getUsersById(['id' => $value['id'], 'select' => ['users.user_id']]);
        }

        return $response->withJson(['groups' => $groups]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function getDetailledById(Request $request, Response $response, array $args): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $group = GroupModel::getById(['id' => $args['id'], 'select' => ['id', 'group_id', 'group_desc']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        $group['security'] = GroupModel::getSecurityByGroupId(['groupId' => $group['group_id']]);
        $group['users'] = GroupModel::getUsersById(
            [
                'id'     => $args['id'],
                'select' => ['users.id', 'users.user_id', 'users.firstname', 'users.lastname', 'users.status']
            ]
        );
        $group['baskets'] = GroupBasketModel::getBasketsByGroupId(
            [
                'select'  => ['baskets.basket_id', 'baskets.basket_name', 'baskets.basket_desc'],
                'groupId' => $group['group_id']
            ]
        );
        $group['canAdminUsers'] = PrivilegeController::hasPrivilege(
            ['privilegeId' => 'admin_users', 'userId' => $GLOBALS['id']]
        );
        $group['canAdminBaskets'] = PrivilegeController::hasPrivilege(
            ['privilegeId' => 'admin_baskets', 'userId' => $GLOBALS['id']]
        );
        $group['privileges'] = PrivilegeModel::getPrivilegesByGroupId(['groupId' => $args['id']]);
        $group['lockAdvancedPrivileges'] = PrivilegeController::isAdvancedPrivilegesLocked();

        $allowedUsers = [];
        $isRoot = UserController::isRoot(['id' => $GLOBALS['id']]);
        if (!$isRoot) {
            $users = array_column($group['users'], 'id');
            $entities = EntityModel::getAllEntitiesByUserId(['userId' => $GLOBALS['id']]);
            if (!empty($entities) && !empty($users)) {
                $allowedUsers = UserEntityModel::getWithUsers([
                    'select' => ['DISTINCT users.id'],
                    'where'  => ['users_entities.entity_id in (?)', 'status != ?', 'users.id in (?)'],
                    'data'   => [$entities, 'DEL', $users]
                ]);
            }
            $usersNoEntities = UserEntityModel::getUsersWithoutEntities(['select' => ['id']]);
            $allowedUsers = array_merge($allowedUsers, $usersNoEntities);
            $allowedUsers = array_column($allowedUsers, 'id');
        }

        foreach ($group['users'] as $key => $user) {
            $group['users'][$key]['allowed'] = $isRoot ?: in_array($user['id'], $allowedUsers);
        }

        return $response->withJson(['group' => $group]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $aArgs
     * @return Response
     * @throws Exception
     */
    public function reassignUsers(Request $request, Response $response, array $aArgs): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $group = GroupModel::getById(['id' => $aArgs['id'], 'select' => ['group_id']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }
        $newGroup = GroupModel::getById(['id' => $aArgs['newGroupId'], 'select' => ['group_id']]);
        if (empty($newGroup)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }
        $oldGroupUsers = GroupModel::getUsersById(['id' => $aArgs['id'], 'select' => ['users.id']]);
        $newGroupUsers = GroupModel::getUsersById(['id' => $aArgs['newGroupId'], 'select' => ['users.id']]);

        //Mapped array to have only user_id
        $oldGroupUsers = array_map(function ($entry) {
            return $entry['id'];
        }, $oldGroupUsers);

        $newGroupUsers = array_map(function ($entry) {
            return $entry['id'];
        }, $newGroupUsers);

        $ignoredUsers = [];
        foreach ($oldGroupUsers as $user) {
            if (in_array($user, $newGroupUsers)) {
                $ignoredUsers[] = $user;
            }
        }

        $where = ['group_id = ?'];
        $data = [$aArgs['id']];
        if (!empty($ignoredUsers)) {
            $where[] = 'user_id NOT IN (?)';
            $data[] = $ignoredUsers;
        }

        UserGroupModel::update(['set' => ['group_id' => $aArgs['newGroupId']], 'where' => $where, 'data' => $data]);

        return $response->withJson(['success' => 'success']);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function getIndexingInformationsById(Request $request, Response $response, array $args): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $group = GroupModel::getById(['id' => $args['id'], 'select' => ['can_index', 'indexation_parameters']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        $group['canIndex'] = $group['can_index'];
        $group['indexationParameters'] = json_decode($group['indexation_parameters'], true);
        unset($group['can_index'], $group['indexation_parameters']);

        $allActions = ActionModel::get(
            [
                'select' => ['id', 'label_action'],
                'where'  => ['component in (?)'],
                'data'   => [GroupController::INDEXING_ACTIONS]
            ]
        );

        $allEntities = EntityModel::get([
            'select'    => ['e1.id', 'e1.entity_id', 'e1.entity_label', 'e1.parent_entity_id', 'e2.id as parent_id'],
            'table'     => ['entities e1', 'entities e2'],
            'left_join' => ['e1.parent_entity_id = e2.entity_id'],
            'where'     => ['e1.enabled = ?'],
            'data'      => ['Y']
        ]);
        $allEntities = EntityModel::removeOrphanedEntities($allEntities);

        foreach ($allEntities as $key => $value) {
            $allEntities[$key]['id'] = $value['id'];
            if (empty($value['parent_id'])) {
                $allEntities[$key]['parent'] = '#';
                $allEntities[$key]['icon'] = "fa fa-building";
            } else {
                $allEntities[$key]['parent'] = $value['parent_id'];
                $allEntities[$key]['icon'] = "fa fa-sitemap";
            }
            $allEntities[$key]['state']['opened'] = true;
            if (in_array($value['id'], $group['indexationParameters']['entities'])) {
                $allEntities[$key]['state']['selected'] = true;
            }

            $allEntities[$key]['text'] = $value['entity_label'];
        }

        return $response->withJson(['group' => $group, 'actions' => $allActions, 'entities' => $allEntities]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function updateIndexingInformations(Request $request, Response $response, array $args): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        if (!Validator::arrayType()->notEmpty()->validate($body)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body is empty or not an array']);
        }

        $group = GroupModel::getById(['id' => $args['id'], 'select' => ['indexation_parameters']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        $set = [];
        $indexationParameters = json_decode($group['indexation_parameters'], true);

        if (isset($body['canIndex']) && is_bool($body['canIndex'])) {
            $set['can_index'] = $body['canIndex'] ? 'true' : 'false';
        }
        if (isset($body['actions']) && is_array($body['actions'])) {
            if (!empty($body['actions'])) {
                $countActions = ActionModel::get([
                    'select' => ['count(1)'],
                    'where'  => ['id in (?)'],
                    'data'   => [$body['actions']]
                ]);
                if ($countActions[0]['count'] != count($body['actions'])) {
                    return $response->withStatus(400)->withJson([
                        'errors' => 'Body actions contains invalid actions'
                    ]);
                }
                foreach ($body['actions'] as $key => $action) {
                    $body['actions'][$key] = (string)$action;
                }
            }
            $indexationParameters['actions'] = $body['actions'];
        }
        if (isset($body['entities']) && is_array($body['entities'])) {
            if (!empty($body['entities'])) {
                $countEntities = EntityModel::get([
                    'select' => ['count(1)'],
                    'where'  => ['id in (?)'],
                    'data'   => [$body['entities']]
                ]);
                if ($countEntities[0]['count'] != count($body['entities'])) {
                    return $response->withStatus(400)->withJson([
                        'errors' => 'Body entities contains invalid entities'
                    ]);
                }
                foreach ($body['entities'] as $key => $entity) {
                    $body['entities'][$key] = (string)$entity;
                }
            }
            $indexationParameters['entities'] = $body['entities'];
        }
        if (isset($body['keywords']) && is_array($body['keywords'])) {
            $indexationParameters['keywords'] = $body['keywords'];
        }
        $set['indexation_parameters'] = json_encode($indexationParameters);

        GroupModel::update([
            'set'   => $set,
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        return $response->withStatus(204);
    }

    /**
     * @param array $args
     * @return string
     * @throws Exception
     */
    public static function getGroupsClause(array $args): string
    {
        ValidatorModel::notEmpty($args, ['userId', 'userId']);

        $groups = UserModel::getGroupsById(['id' => $args['userId']]);
        $groupsClause = '';
        foreach ($groups as $key => $group) {
            if (!empty($group['where_clause'])) {
                $groupClause = PreparedClauseController::getPreparedClause([
                    'clause' => $group['where_clause'],
                    'userId' => $args['userId']
                ]);
                if ($key > 0) {
                    $groupsClause .= ' or ';
                }
                $groupsClause .= "({$groupClause})";
            }
        }

        return $groupsClause;
    }
}
