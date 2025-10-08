<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ResourceAlreadySignProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ResourceAlreadySignProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            "Resource already signed",
            400
        );
    }
}
