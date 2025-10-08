<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurrentUserRepository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Infrastructure;

use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\User\Domain\User;
use SrcCore\controllers\AuthenticationController;
use SrcCore\controllers\CoreController;

class CurrentUserInformations implements CurrentUserInterface
{
    public function getCurrentUser(): User
    {
        return User::createFromArray([
            'id' => $GLOBALS['id']
        ]);
    }
    public function getCurrentUserId(): int
    {
        return $GLOBALS['id'];
    }

    public function getCurrentUserLogin(): string
    {
        return $GLOBALS['login'];
    }

    /**
     * @return string
     */
    public function getCurrentUserToken(): string
    {
        return $GLOBALS['token'];
    }

    public function generateNewToken(): string
    {
        return AuthenticationController::getJWT();
    }

    public function setCurrentUser(int $userId): void
    {
        CoreController::setGlobals(['userId' => $userId]);
    }
}
