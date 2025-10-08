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

namespace ExternalSignatoryBook\Domain\Exceptions;

use Exception;

class ParameterCanNotBeEmptyException extends Exception
{
    public function __construct(string $parameterName, string $mustBe = null)
    {
        $msg = "Parameter $parameterName cannot be empty";

        if (!empty($mustBe)) {
            $msg .= " and must be $mustBe";
        }
        parent::__construct($msg, 400);
    }
}
