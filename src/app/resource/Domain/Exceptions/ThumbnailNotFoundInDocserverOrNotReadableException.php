<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ExceptionThumbnailNotFoundInDocserverOrNotReadable class
 * @author dev@maarch.org
 */

namespace Resource\Domain\Exceptions;

use Exception;

class ThumbnailNotFoundInDocserverOrNotReadableException extends Exception
{
    public function __construct()
    {
        parent::__construct("Thumbnail not found in docserver or not readable", 400);
    }
}
