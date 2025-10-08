<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SetaPdfResultException class
 * @author dev@maarch.org
 */

namespace Resource\Domain\Exceptions;

use Exception;

class SetaPdfResultException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct("$message", 500);
    }
}
