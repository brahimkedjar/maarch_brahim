<?php

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GetGroupPrivilegeFailedProblem extends Problem
{
    public function __construct()
    {
        parent::__construct("Get group privileges in signature book failed :  ", 403);
    }
}
