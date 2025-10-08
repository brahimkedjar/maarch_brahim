<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Book User Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

interface SignatureBookUserServiceInterface
{
    /**
     * @param UserInterface $user
     * @return array|int
     */
    public function createUser(UserInterface $user): array|int;

    /**
     * @param UserInterface $user
     * @return array|bool
     */
    public function updateUser(UserInterface $user): array|bool;

    /**
     * @param UserInterface $user
     * @return array|bool
     */
    public function deleteUser(UserInterface $user): array|bool;

    /**
     * @param int $id
     * @return bool
     */
    public function doesUserExists(int $id): bool;

    /**
     * @param SignatureBookServiceConfig $config
     *
     * @return SignatureBookUserServiceInterface
     */
    public function setConfig(SignatureBookServiceConfig $config): SignatureBookUserServiceInterface;
}
