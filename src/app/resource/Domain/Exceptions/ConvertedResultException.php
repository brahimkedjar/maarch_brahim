<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ConvertedResultException class
 * @author dev@maarch.org
 */

namespace Resource\Domain\Exceptions;

use Exception;

class ConvertedResultException extends Exception
{
    public function __construct(string $message) // TODO set message
    {
        parent::__construct("Conversion error : $message", 500);
    }
}
