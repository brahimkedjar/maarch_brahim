<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve User Signature
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Stamp;

use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\CannotAccessOtherUsersSignaturesProblem;
use MaarchCourrier\SignatureBook\Domain\UserSignature;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;

class RetrieveUserStamps
{
    /**
     * @param UserRepositoryInterface $user
     * @param SignatureRepositoryInterface $signatureService
     * @param CurrentUserInformations $currentUserInformations
     */
    public function __construct(
        private readonly UserRepositoryInterface $user,
        private readonly SignatureRepositoryInterface $signatureService,
        private readonly CurrentUserInterface $currentUserInformations
    ) {
    }

    /**
     * @param int $userId
     * @return UserSignature[]
     * @throws UserDoesNotExistProblem|CannotAccessOtherUsersSignaturesProblem
     */
    public function getUserSignatures(int $userId): array
    {
        $user = $this->user->getUserById($userId);
        if ($user === null) {
            throw new UserDoesNotExistProblem();
        }

        if ($this->currentUserInformations->getCurrentUserId() !== $user->getId()) {
            throw new CannotAccessOtherUsersSignaturesProblem();
        }

        return $this->signatureService->getSignaturesByUserId($user->getId());
    }
}
