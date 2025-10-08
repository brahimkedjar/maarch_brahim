<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Does Not Exist Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class UserDoesNotExistProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            "User does not exist",
            400
        );
    }
}
