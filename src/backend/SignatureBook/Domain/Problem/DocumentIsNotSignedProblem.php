<?php

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class DocumentIsNotSignedProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            "Document is not signed",
            400
        );
    }
}
