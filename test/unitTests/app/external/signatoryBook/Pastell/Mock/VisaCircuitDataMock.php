<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

declare(strict_types=1);

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock;

use ExternalSignatoryBook\pastell\Domain\VisaCircuitDataInterface;

class VisaCircuitDataMock implements VisaCircuitDataInterface
{
    public string $signatoryUserId = '';

    /**
     * @param int $resId
     * @return string[]
     */
    public function getNextSignatory(int $resId): array
    {
        return [
            'userId' => $this->signatoryUserId
        ];
    }
}
