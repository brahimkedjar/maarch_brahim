<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Update In Maarch Parapheur Failed Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GroupUpdateInSignatureBookFailedProblem extends Problem
{
    public function __construct(array $content)
    {
        parent::__construct(
            "Group update in signature book failed : " . $content['errors'],
            500,
            [
                'errors' => $content['errors']
            ]
        );
    }
}
