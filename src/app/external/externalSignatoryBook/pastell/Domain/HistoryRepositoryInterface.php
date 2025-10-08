<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Pastell HistoryRepository Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace ExternalSignatoryBook\pastell\Domain;

interface HistoryRepositoryInterface
{
    /**
     * @param int $id
     * @param string $message
     * @return void
     */
    public function addLogInHistory(int $id, string $message): void;
}
