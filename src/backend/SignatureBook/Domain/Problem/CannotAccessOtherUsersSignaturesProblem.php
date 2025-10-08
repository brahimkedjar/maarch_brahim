<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Access Denied Exception
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CannotAccessOtherUsersSignaturesProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            "Access Denied. You do not have permission to access other users signatures.",
            400
        );
    }
}
