<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief History Repository interface
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\Domain\Ports;

interface HistoryRepositoryInterface
{
    /**
     * @param string    $recordId
     * @param int       $userId
     * @param string    $info
     * @return void
     */
    public function addHistoryForResource(string $recordId, int $userId, string $info): void;
}
