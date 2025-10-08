<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ResourceDoesNotExistException class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ResourceDoesNotExistProblem extends Problem
{
    public function __construct()
    {
        parent::__construct("Document does not exist", 400);
    }
}
