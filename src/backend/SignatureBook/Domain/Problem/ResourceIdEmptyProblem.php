<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ResourceIdEmptyProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ResourceIdEmptyProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            "res_id parameter is missing in payload",
            400
        );
    }
}
