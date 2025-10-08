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
use SrcCore\models\CoreConfigModel;

class TestErrorTypoInUpdate implements AutoUpdateInterface
{
    private static $testConfigPath = 'config/config.json.backup';
    private static $originalConfigPath = 'config/config.json';

    public function backup(): void
    {
        try {
            $config = CoreConfigModel::getJsonLoaded(['path' => self::$originalConfigPath]);
            file_put_contents(self::$testConfigPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function update(): void
    {
        try {
            $config = CoreConfigModel::getJsonLoaded(['path' => self::$originalConfigPath]);
            $config['PhpUnitTest'] = [
                'hello'     => 'world',
                'maarch'    => 'courrier'
            ];

            // simulate en error
            file_put_contents(self::$testConfigPath_, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function rollback(): void
    {
        try {
            $config = CoreConfigModel::getJsonLoaded(['path' => self::$testConfigPath]);
            unlink(self::$testConfigPath);
            file_put_contents(self::$originalConfigPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }
}
return TestErrorTypoInUpdate::class; // The file return the class name