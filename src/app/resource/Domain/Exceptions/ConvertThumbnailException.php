<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ConvertThumbnailException class
 * @author dev@maarch.org
 */

namespace Resource\Domain\Exceptions;

use Exception;

class ConvertThumbnailException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct("Thumbnail conversion failed : $message", 500);
    }
}
