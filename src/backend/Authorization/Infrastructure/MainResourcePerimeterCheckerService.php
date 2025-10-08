<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Access Control Service class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authorization\Infrastructure;

use Exception;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourcePerimeterCheckerInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use Resource\controllers\ResController;

class MainResourcePerimeterCheckerService implements MainResourcePerimeterCheckerInterface
{
    /**
     * @param int $resId
     * @param UserInterface $user
     *
     * @return bool
     * @throws Exception
     */
    public function hasRightByResId(int $resId, UserInterface $user): bool
    {
        return ResController::hasRightByResId(['resId' => [$resId], 'userId' => $user->getId()]);
    }
}
