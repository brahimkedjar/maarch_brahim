<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource repository infrastructure
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\Infrastructure;

use ExternalSignatoryBook\Domain\Ports\ResourceRepositoryInterface;
use Resource\models\ResModel;

class ResourceRepository implements ResourceRepositoryInterface
{
    public function removeExternalLink(int $id, string $externalId): void
    {
        ResModel::removeExternalLink(['resId' => $id, 'externalId' => $externalId]);
    }
}
