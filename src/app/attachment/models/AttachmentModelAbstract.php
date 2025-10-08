<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment Model Abstract
 * @author dev@maarch.org
 */

namespace Attachment\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

abstract class AttachmentModelAbstract
{
    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function get(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy', 'groupBy']);
        ValidatorModel::intType($aArgs, ['limit']);

        return DatabaseModel::select([
            'select'   => $aArgs['select'],
            'table'    => ['res_attachments'],
            'where'    => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'     => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by' => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'groupBy'  => empty($aArgs['groupBy']) ? [] : $aArgs['groupBy'],
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

        $attachment = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['res_attachments'],
            'where'  => ['res_id = ?'],
            'data'   => [$aArgs['id']],
        ]);

        if (empty($attachment[0])) {
            return [];
        }

        return $attachment[0];
    }

    /**
     * @param array $args
     * @return int
     * @throws Exception
     */
    public static function create(array $args): int
    {
        ValidatorModel::notEmpty($args, [
            'format', 'typist', 'creation_date', 'docserver_id', 'path',
            'filename', 'fingerprint', 'filesize', 'status', 'relation'
        ]);
        ValidatorModel::stringType($args, [
            'format', 'creation_date', 'docserver_id', 'path',
            'filename', 'fingerprint', 'status'
        ]);
        ValidatorModel::intVal($args, ['filesize', 'relation', 'typist']);

        if (empty($args['res_id'])) {
            $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'res_attachment_res_id_seq']);
            $args['res_id'] = $nextSequenceId;
        }

        DatabaseModel::insert([
            'table'         => 'res_attachments',
            'columnsValues' => $args
        ]);

        return $args['res_id'];
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
            'table'   => 'res_attachments',
            'set'     => $args['set'] ?? [],
            'postSet' => $args['postSet'] ?? [],
            'where'   => $args['where'],
            'data'    => $args['data']
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return bool
     * @throws Exception
     */
    public static function freezeAttachment(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['resId', 'externalId']);
        ValidatorModel::intType($aArgs, ['resId']);

        $aAttachment = DatabaseModel::select([
            'select' => ['external_id'],
            'table'  => ['res_attachments'],
            'where'  => ['res_id = ?'],
            'data'   => [$aArgs['resId']],
        ]);

        $externalId = json_decode($aAttachment[0]['external_id'], true);
        $externalId['signatureBookId'] = empty($aArgs['externalId']) ? null : $aArgs['externalId'];

        DatabaseModel::update([
            'table' => 'res_attachments',
            'set'   => ['status' => 'FRZ', 'external_id' => json_encode($externalId)],
            'where' => ['res_id = ?'],
            'data'  => [$aArgs['resId']]
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return bool
     * @throws Exception
     */
    public static function setInSignatureBook(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::boolType($aArgs, ['inSignatureBook']);

        if ($aArgs['inSignatureBook']) {
            $aArgs['inSignatureBook'] = 'true';
        } else {
            $aArgs['inSignatureBook'] = 'false';
        }

        DatabaseModel::update([
            'table' => 'res_attachments',
            'set'   => [
                'in_signature_book' => $aArgs['inSignatureBook']
            ],
            'where' => ['res_id = ?'],
            'data'  => [$aArgs['id']],
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     * @return bool
     * @throws Exception
     */
    public static function setInSendAttachment(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::boolType($aArgs, ['inSendAttachment']);

        if ($aArgs['inSendAttachment']) {
            $aArgs['inSendAttachment'] = 'true';
        } else {
            $aArgs['inSendAttachment'] = 'false';
        }

        DatabaseModel::update([
            'table' => 'res_attachments',
            'set'   => [
                'in_send_attach' => $aArgs['inSendAttachment']
            ],
            'where' => ['res_id = ?'],
            'data'  => [$aArgs['id']],
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function hasAttachmentsSignedByResId(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['resId', 'userId']);
        ValidatorModel::intVal($args, ['resId', 'userId']);

        $attachments = DatabaseModel::select([
            'select' => [1],
            'table'  => ['res_attachments'],
            'where'  => ['res_id_master = ?', 'signatory_user_serial_id = ?'],
            'data'   => [$args['resId'], $args['userId']],
        ]);

        if (empty($attachments)) {
            return false;
        }

        return true;
    }

    /**
     * @param int $resIdMaster
     * @param int $originId
     * @return array
     * @throws Exception
     */
    public static function getLastVersionByOriginId(int $resIdMaster, int $originId): array
    {
        $attachment = DatabaseModel::select([
            'select'   => ['res_id_master', 'origin_id', 'status', 'res_id'],
            'table'    => ['res_attachments'],
            'where'    => ['res_id = ? OR (res_id_master = ? AND origin_id = ?)'],
            'data'     => [$originId, $resIdMaster, $originId],
            'order_by' => ['relation DESC'],
            'limit'    => 1
        ]);

        if (empty($attachment[0])) {
            return [];
        }

        return $attachment[0];
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

        DatabaseModel::update([
            'table' => 'res_attachments',
            'set'   => [
                'status' => 'DEL'
            ],
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
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
            'table'   => 'res_attachments',
            'set'     => ['status' => 'A_TRA'],
            'postSet' => ['external_id' => "external_id - 'signatureBookId'", 'external_state' => "'{}'::jsonb"],
            'where'   => ['res_id = ?', "external_id->>'signatureBookId' = ?"],
            'data'    => [$args['resId'], $args['externalId']]
        ]);

        return true;
    }
}
