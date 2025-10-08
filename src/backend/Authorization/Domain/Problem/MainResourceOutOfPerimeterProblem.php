<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Out Of Perimeter Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authorization\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MainResourceOutOfPerimeterProblem extends Problem
{
    public function __construct()
    {
        parent::__construct("Document out of perimeter", 403);
    }
}
