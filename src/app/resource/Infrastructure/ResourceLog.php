<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource file
 * @author dev@maarch.org
 */

namespace Resource\Infrastructure;

use Resource\Domain\Ports\ResourceLogInterface;
use SrcCore\controllers\LogsController;

class ResourceLog implements ResourceLogInterface
{
    /**
     * @param string $logLevel
     * @param int $recordId
     * @param string $message
     *
     * @return  void
     */
    public function logThumbnailEvent(string $logLevel, int $recordId, string $message): void
    {
        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'resources',
            'level'     => $logLevel,
            'tableName' => 'res_letterbox',
            'recordId'  => $recordId,
            'eventType' => 'thumbnail',
            'eventId'   => $message
        ]);
    }
}
