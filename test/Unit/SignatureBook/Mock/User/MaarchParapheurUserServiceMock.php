<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MaarchParapheurUserServiceMock
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\User;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookUserServiceInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

class MaarchParapheurUserServiceMock implements SignatureBookUserServiceInterface
{
    public bool $createUserCalled = false;
    public int|array $userCreated = 12;
    public bool $updateUserCalled = false;
    public bool|array $userUpdated = false;
    public array|bool $deletedUser = false;
    public bool $deleteUserCalled = false;
    public bool $userExists = false;

    public SignatureBookServiceConfig $config;

    /**
     * @param UserInterface $user
     * @return array|int
     */
    public function createUser(UserInterface $user): array|int
    {
        $this->createUserCalled = true;
        return $this->userCreated;
    }

    /**
     * @param UserInterface $user
     * @return array|bool
     */
    public function updateUser(UserInterface $user): array|bool
    {
        $this->updateUserCalled = true;
        $user->setFirstname('firstname2');
        return $this->userUpdated;
    }

    /**
     * @param UserInterface $user
     * @return array|bool
     */
    public function deleteUser(UserInterface $user): array|bool
    {
        $this->deleteUserCalled = true;
        return $this->deletedUser;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function doesUserExists(int $id): bool
    {
        if (!$this->userExists) {
            return false;
        }
        return true;
    }

    /**
     * @param SignatureBookServiceConfig $config
     * @return SignatureBookUserServiceInterface
     */
    public function setConfig(SignatureBookServiceConfig $config): SignatureBookUserServiceInterface
    {
        $this->config = $config;
        return $this;
    }
}
