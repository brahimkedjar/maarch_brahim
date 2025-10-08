<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief EnvironnementMock
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Functional\Core\Error\Mock;

use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;

class EnvironmentMock implements EnvironmentInterface
{
    public bool $debug = false;

    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @return bool
     */
    public function isNewInternalParapheurEnabled(): bool
    {
        return true;
    }
}
