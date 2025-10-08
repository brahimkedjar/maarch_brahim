<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Repository Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Core\Domain\MainResource\Port;

interface MainResourceRepositoryInterface
{
    public function getMainResourceByResId(int $resId): ?MainResourceInterface;
}
