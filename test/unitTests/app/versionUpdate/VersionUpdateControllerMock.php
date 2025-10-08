<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Version Update Controller custom mock
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\app\versionUpdate;

use VersionUpdate\controllers\VersionUpdateController;

class VersionUpdateControllerMock
{
    public static function isMigrating(): bool
    {
        if (!file_exists(VersionUpdateController::UPDATE_LOCK_FILE)) {
            touch(VersionUpdateController::UPDATE_LOCK_FILE);
        }
        return true;
    } 
}