<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ListInstanceRepositoryInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\SignatureBook\Domain\ListInstance;

interface ListInstanceRepositoryInterface
{
    /**
     * @param int $resId
     * @return ListInstance
     */
    public function getNextInCircuit(int $resId): ListInstance;
    /**
     * @param array $args
     * @return ListInstance[]
     */
    public function getListInstanceCircuit(array $args): array;
    public function updateListInstance(ListInstance $listInstance, array $set): void;
}
