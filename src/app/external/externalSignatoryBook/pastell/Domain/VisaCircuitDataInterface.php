<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Bisa Circuit Data Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace ExternalSignatoryBook\pastell\Domain;

interface VisaCircuitDataInterface
{
    /**
     * @param int $resId
     * @return array
     */
    public function getNextSignatory(int $resId): array;
}
