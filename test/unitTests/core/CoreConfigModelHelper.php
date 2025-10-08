<?php

namespace MaarchCourrier\Tests\core;

use SrcCore\models\CoreConfigModel;

class CoreConfigModelHelper extends CoreConfigModel
{
    /**
     * @param mixed $customId
     */
    public static function setCustomId($customId): void
    {
        parent::$customId = $customId;
    }
}
