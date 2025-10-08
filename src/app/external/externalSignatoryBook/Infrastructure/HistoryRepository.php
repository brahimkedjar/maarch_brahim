<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief History repository infrastructure
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\Infrastructure;

use ExternalSignatoryBook\Domain\Ports\HistoryRepositoryInterface;
use History\controllers\HistoryController;

class HistoryRepository implements HistoryRepositoryInterface
{
    /**
     * @param string    $recordId
     * @param int       $userId
     * @param string    $info
     * @return void
     */
    public function addHistoryForResource(string $recordId, int $userId, string $info): void
    {
        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $recordId,
            'eventType' => 'ACTION#1',
            'eventId'   => '1',
            'userId'    => $userId,
            'info'      => $info
        ]);
    }
}
