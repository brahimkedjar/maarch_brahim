<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurlRequestErrorProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CurlRequestErrorProblem extends Problem
{
    public function __construct(int $httpCode, array $content)
    {
        parent::__construct(
            "Error during external parapheur request : " . $content['errors'],
            $httpCode,
            [
                'errors' => $content['errors']
            ]
        );
    }
}
