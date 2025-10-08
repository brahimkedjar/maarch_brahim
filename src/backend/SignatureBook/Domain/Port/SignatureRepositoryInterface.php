<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\SignatureBook\Domain\UserSignature;

interface SignatureRepositoryInterface
{
    /**
     * Get user signatures by user ID.
     *
     * @param int $userId User ID.
     * @return UserSignature[] Array of user signatures.
     */
    public function getSignaturesByUserId(int $userId): array;
}
