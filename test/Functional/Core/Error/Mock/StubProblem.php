<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief StubProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Functional\Core\Error\Mock;

use MaarchCourrier\Core\Domain\Problem\Problem;

class StubProblem extends Problem
{
    public function __construct(string $value)
    {
        parent::__construct(
            'My custom problem : ' . $value,
            418,
            [
                'value' => $value
            ]
        );
    }
}
