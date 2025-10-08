<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureHistoryService class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure;

use Attachment\models\AttachmentModel;
use History\controllers\HistoryController;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureHistoryServiceInterface;
use Resource\models\ResModel;

class SignatureHistoryService implements SignatureHistoryServiceInterface
{
    public function historySignatureValidation(int $resId, ?int $resIdMaster): void
    {
        if (!empty($resIdMaster)) {
            $attachment = AttachmentModel::getById(['id' => $resId, 'select' => ['title']]);

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $resIdMaster,
                'eventType' => 'SIGN',
                'eventId'   => 'resourceSign',
                'info'      => _ATTACHMENT_SIGNED . " : " . $attachment['title']
            ]);

            HistoryController::add([
                'tableName' => 'res_attachments',
                'recordId'  => $resId,
                'eventType' => 'SIGN',
                'eventId'   => 'attachmentSign',
                'info'      => _ATTACHMENT_SIGNED . " : " . $attachment['title']
            ]);
        } else {
            $mainResource = ResModel::getById(['resId' => $resId, 'select' => ['subject']]);
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $resId,
                'eventType' => 'SIGN',
                'eventId'   => 'resourceSign',
                'info'      => _MAIN_RESOURCE_SIGNED . " : " . $mainResource['subject']
            ]);
        }
    }

    public function historySignatureRefus(int $resId, ?int $resIdMaster): void
    {
        if (!empty($resIdMaster)) {
            $attachment = AttachmentModel::getById(['resId' => $resId, 'select' => ['title']]);
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $resIdMaster,
                'eventType' => 'SIGN',
                'eventId'   => 'resourceSign',
                'info'      => _ATTACHMENT_SIGN_REFUSED . " : " . $attachment['title']
            ]);

            HistoryController::add([
                'tableName' => 'res_attachments',
                'recordId'  => $resId,
                'eventType' => 'SIGN',
                'eventId'   => 'attachmentSign',
                'info'      => _ATTACHMENT_SIGN_REFUSED . " : " . $attachment['title']
            ]);
        } else {
            $mainResource = ResModel::getById(['resId' => $resId, 'select' => ['subject']]);
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $resId,
                'eventType' => 'SIGN',
                'eventId'   => 'resourceSign',
                'info'      => _MAIN_RESOURCE_SIGN_REFUSED . " : " . $mainResource['subject']
            ]);
        }
    }

    public function historySignatureError(int $resId, ?int $resIdMaster): void
    {
        HistoryController::add([
            'tableName' => (empty($resIdMaster)) ? 'res_letterbox' : 'res_attachments',
            'recordId'  => $resId,
            'eventType' => 'SIGN',
            'eventId'   => (empty($resIdMaster)) ? 'resourceSign' : 'attachmentSign',
            'info'      => 'Error during signature process'
        ]);
    }
}
