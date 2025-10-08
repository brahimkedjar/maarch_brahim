<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Pastell HistoryRepository
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\pastell\Infrastructure;

use ExternalSignatoryBook\pastell\Domain\HistoryRepositoryInterface;
use History\controllers\HistoryController;

class HistoryRepository implements HistoryRepositoryInterface
{
    /**
     * @param int $id
     * @param string $message
     * @return void
     */
    public function addLogInHistory(int $id, string $message): void
    {
        $userRoot = new UserRoot();

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $id,
            'eventType' => 'ACTION#1',
            'eventId'   => '1',
            'userId'    => $userRoot->getUserRootId(),
            'info'      => "[Pastell api] {$message}"
        ]);
    }
}
