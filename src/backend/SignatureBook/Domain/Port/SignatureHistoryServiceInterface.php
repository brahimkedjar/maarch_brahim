<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureHistoryServiceInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

interface SignatureHistoryServiceInterface
{
    public function historySignatureValidation(int $resId, ?int $resIdMaster): void;

    public function historySignatureRefus(int $resId, ?int $resIdMaster): void;

    public function historySignatureError(int $resId, ?int $resIdMaster): void;
}
