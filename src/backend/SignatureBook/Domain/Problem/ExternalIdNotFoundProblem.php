<?php

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ExternalIdNotFoundProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            "Document not liked to MaarchParapheur API",
            400
        );
    }
}
