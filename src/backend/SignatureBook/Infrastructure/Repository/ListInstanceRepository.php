<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ListInstanceRepository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Repository;

use Entity\models\ListInstanceModel;
use MaarchCourrier\SignatureBook\Domain\ListInstance;
use MaarchCourrier\SignatureBook\Domain\Port\ListInstanceRepositoryInterface;

class ListInstanceRepository implements ListInstanceRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getNextInCircuit(int $resId): ListInstance
    {
        $data = ListInstanceModel::get([
            'select'  => ['listinstance_id', 'item_id'],
            'where'   => ['res_id = ?', 'difflist_type = ?', 'process_date is null'],
            'data'    => [$resId, 'VISA_CIRCUIT'],
            'orderBy' => ['listinstance_id'],
            'limit'   => 1
        ]);
        $listInstance = [];

        foreach ($data as $datum) {
            $listInstance = (new ListInstance())
                ->setListInstanceId($datum['listinstance_id'])
                ->setItemId($datum['item_id']);
        }

        return $listInstance;
    }

    /**
     * @param array $args
     * @return ListInstance[]
     */
    public function getListInstanceCircuit(array $args): array
    {
        $data = ListInstanceModel::get([
            'select'  => ['requested_signature', 'item_id', 'listinstance_id'],
            'where'   => ['res_id = ?', 'difflist_type = ?', 'process_date is null'],
            'data'    => [$args['resId'], 'VISA_CIRCUIT'],
            'orderBy' => ['listinstance_id']
        ]);
        $listInstance = [];

        foreach ($data as $datum) {
            $listInstance[] = (new ListInstance())
                    ->setListInstanceId($datum['listinstance_id'])
                    ->setItemId($datum['item_id'])
                    ->setRequestedSignature($datum['requested_signature']);
        }

        return $listInstance;
    }

    /**
     * @param array $set
     * @param ListInstance $listInstance
     * @return void
     */
    public function updateListInstance(ListInstance $listInstance, array $set): void
    {
        ListInstanceModel::update([
            'set'   => $set,
            'where' => ['listinstance_id = ?'],
            'data'  => [$listInstance->getListInstanceId()]
        ]);
    }
}
