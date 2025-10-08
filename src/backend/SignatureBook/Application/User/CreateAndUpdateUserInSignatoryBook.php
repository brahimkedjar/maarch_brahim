<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Create And Update User In Signatory Book
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\User;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookUserServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserCreateInSignatureBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserUpdateInSignatureBookFailedProblem;

class CreateAndUpdateUserInSignatoryBook
{
    /**
     * @param SignatureBookUserServiceInterface $signatureBookUserService
     * @param SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader
     */
    public function __construct(
        private readonly SignatureBookUserServiceInterface $signatureBookUserService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader
    ) {
    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserCreateInSignatureBookFailedProblem
     * @throws UserUpdateInSignatureBookFailedProblem
     */
    public function createAndUpdateUser(UserInterface $user): UserInterface
    {
        $signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->signatureBookUserService->setConfig($signatureBook);

        $externalId = (int)array_values($user->getExternalId());

        if (!empty($externalId)) {
            if ($this->signatureBookUserService->doesUserExists($externalId)) {
                $userIsUpdated = $this->signatureBookUserService->updateUser($user);
                if (!empty($userIsUpdated['errors'])) {
                    throw new UserUpdateInSignatureBookFailedProblem($userIsUpdated);
                }
            } else {
                $existingIds = $user->getExternalId();
                $maarchParapheurUserId = $this->signatureBookUserService->createUser($user);
                if (!empty($maarchParapheurUserId['errors'])) {
                    throw new UserCreateInSignatureBookFailedProblem($maarchParapheurUserId);
                } else {
                    $existingIds['internalParapheur'] = $maarchParapheurUserId;
                    $user->setExternalId($existingIds);
                }
            }
        } else {
            $maarchParapheurUserId = $this->signatureBookUserService->createUser($user);
            if (!empty($maarchParapheurUserId['errors'])) {
                throw new UserCreateInSignatureBookFailedProblem($maarchParapheurUserId);
            } else {
                $external['internalParapheur'] = $maarchParapheurUserId;
                $user->setExternalId($external);
            }
        }
        return $user;
    }
}
