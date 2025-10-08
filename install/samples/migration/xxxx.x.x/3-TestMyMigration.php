<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Migration Test Script
 * @author dev@maarch.org
 */

namespace Migration\xxxx_x_x;

require 'vendor/autoload.php';

use SrcCore\interfaces\AutoUpdateInterface;

class TestMyMigration implements AutoUpdateInterface
{
    public function backup(): void
    {
        try {

        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function update(): void
    {
        try {

        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function rollback(): void
    {
        try {

        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }
}
return TestMyMigration::class; // The file return the class name