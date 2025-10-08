<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ResourceIdMasterNotCorrespondingProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ResourceIdMasterNotCorrespondingProblem extends Problem
{
    public function __construct(int $resId, int $resIdMaster)
    {
        parent::__construct(
            "res_id " . $resId . " is not an attachment of res_id_master " . $resIdMaster,
            400,
            [
                'resId'       => $resId,
                'resIdMaster' => $resIdMaster
            ]
        );
    }
}
