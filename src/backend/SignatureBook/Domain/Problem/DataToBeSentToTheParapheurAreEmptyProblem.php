<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief DataToSentToTheParapheurAreEmptyProblem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class DataToBeSentToTheParapheurAreEmptyProblem extends Problem
{
    /**
     * @param string[] $message
     */
    public function __construct(array $message)
    {
        parent::__construct(
            'Some data for sending to parapheur are missing : ' . implode(', ', $message),
            400,
            [
                "value" => $message
            ]
        );
    }
}
