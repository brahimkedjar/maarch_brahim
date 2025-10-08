<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Service Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Repository;

use MaarchCourrier\SignatureBook\Domain\Port\SignatureRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\UserSignature;
use User\models\UserSignatureModel;

class SignatureRepository implements SignatureRepositoryInterface
{
    /**
     * @param int $userId
     * @return UserSignature[]
     */
    public function getSignaturesByUserId(int $userId): array
    {
        $signatures = UserSignatureModel::getByUserSerialId(['userSerialid' => $userId]);

        if (empty($signatures)) {
            return [];
        }

        $userSignatures = [];
        foreach ($signatures as $s) {
            $userSignatures[] = UserSignature::createFromArray($s);
        }

        return $userSignatures;
    }
}
