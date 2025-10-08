<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurrentUserInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User\Port;

interface CurrentUserInterface
{
    public function getCurrentUser(): UserInterface;
    public function getCurrentUserId(): int;
    public function getCurrentUserLogin(): string;
    public function getCurrentUserToken(): string;
    public function generateNewToken(): string;
    public function setCurrentUser(int $userId): void;
}
