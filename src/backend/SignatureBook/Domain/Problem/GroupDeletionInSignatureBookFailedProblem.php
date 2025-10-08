<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Book User Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GroupDeletionInSignatureBookFailedProblem extends Problem
{
    public function __construct(array $content)
    {
        parent::__construct(
            "Group deletion in signature book failed :  " . $content["errors"],
            500,
            [
                'errors' => $content["errors"]
            ]
        );
    }
}
