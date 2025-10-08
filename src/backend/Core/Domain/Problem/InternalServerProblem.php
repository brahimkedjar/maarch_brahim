<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief InternalServerProblem class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Core\Domain\Problem;

class InternalServerProblem extends Problem
{
    public function __construct(?\Throwable $throwable = null, bool $debug = false)
    {
        $context = [
            'message' => $throwable->getMessage()
        ];

        if ($debug) {
            $context += [
                'file'    => $throwable->getFile(),
                'line'    => $throwable->getLine(),
                'trace'   => $throwable->getTrace()
            ];
        }

        parent::__construct('Internal server error', 500, $context);
    }
}
