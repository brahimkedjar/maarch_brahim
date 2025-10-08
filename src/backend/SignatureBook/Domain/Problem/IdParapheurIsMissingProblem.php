<?php

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class IdParapheurIsMissingProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            "idParapheur is missing in payload attribute (or payload attribute is missing)",
            400
        );
    }
}
