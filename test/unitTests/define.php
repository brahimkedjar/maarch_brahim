<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

use SrcCore\models\CoreConfigModel;
use User\models\UserModel;

require_once 'vendor/autoload.php';

$GLOBALS['login'] = 'superadmin';
$userInfo = UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
$GLOBALS['id'] = $userInfo['id'];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

date_default_timezone_set(CoreConfigModel::getTimezone());

$language = CoreConfigModel::getLanguage();
require_once("src/core/lang/lang-{$language}.php");

$config = file_get_contents('config/config.json');
$config = json_decode($config, true);
$config['config']['newInternalParaph'] = true;
$fp = fopen('config/config.json', 'w');
fwrite($fp, json_encode($config, JSON_PRETTY_PRINT));
fclose($fp);
