<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ListInstanceRepositoryMock class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action;

use MaarchCourrier\SignatureBook\Domain\ListInstance;
use MaarchCourrier\SignatureBook\Domain\Port\ListInstanceRepositoryInterface;

class ListInstanceRepositoryMock implements ListInstanceRepositoryInterface
{
    /**
     * @param array $args
     * @return array
     */
    public function getListInstanceCircuit(array $args): array
    {
        return $args;
    }

    /**
     * @param ListInstance $listInstance
     * @param array $set
     * @return void
     */
    public function updateListInstance(ListInstance $listInstance, array $set): void
    {
        // TODO: Implement updateListInstance() method.
    }

    /**
     * @param int $resId
     * @return ListInstance
     */
    public function getNextInCircuit(int $resId): ListInstance
    {
        // TODO: Implement getNextInCircuit() method.
        return $resId;
    }
}
