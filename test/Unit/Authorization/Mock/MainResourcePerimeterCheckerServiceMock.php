<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MainResourceAccessControlServiceMock class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\Authorization\Mock;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourcePerimeterCheckerInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

class MainResourcePerimeterCheckerServiceMock implements MainResourcePerimeterCheckerInterface
{
    public bool $doesUserHasRight = true;

    public function hasRightByResId(int $resId, UserInterface $user): bool
    {
        return $this->doesUserHasRight;
    }
}
