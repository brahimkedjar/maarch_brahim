<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief StoreResourceProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class StoreResourceProblem extends Problem
{
    public function __construct(string $errors)
    {
        parent::__construct(
            "Error during signed file storage : " . $errors,
            400,
            [
                'errors' => $errors
            ]
        );
    }
}
