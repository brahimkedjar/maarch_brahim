<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Parameter Must Be Greater Than Zero Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class ParameterMustBeGreaterThanZeroException extends Problem
{
    public function __construct(string $parameterName)
    {
        parent::__construct(
            "Parameter '$parameterName' must be greater than 0",
            400,
            [
                'parameterName' => $parameterName
            ]
        );
    }
}
