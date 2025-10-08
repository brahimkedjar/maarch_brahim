<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ParameterCanNotBeEmptyException class
 * @author dev@maarch.org
 */

namespace Resource\Domain\Exceptions;

use Exception;

class ParameterCanNotBeEmptyException extends Exception
{
    public function __construct(string $parameterName, string $shouldBe)
    {
        parent::__construct("Parameter $parameterName can not be empty and should be $shouldBe", 400);
    }
}
