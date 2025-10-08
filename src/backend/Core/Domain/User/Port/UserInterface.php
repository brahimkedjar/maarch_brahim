<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User\Port;

interface UserInterface
{
    /**
     * Create a user object of an array (keys/values) from the database
     * ```
     * User $user = User::createFromArray(['id' => 1, 'firstname' => 'Robert', 'lastname' => 'RENAUD',...]);
     * ```
     *
     * @param array $array
     * @return UserInterface
     */
    public static function createFromArray(array $array = []): UserInterface;

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @param int $id
     * @return void
     */
    public function setId(int $id): void;

    /**
     * @return array
     */
    public function getExternalId(): array;

    /**
     * @param array $externalId
     * @return UserInterface
     */
    public function setExternalId(array $externalId): UserInterface;

    /**
     * @return string
     */
    public function getFirstname(): string;

    /**
     * @param string $firstname
     * @return UserInterface
     */
    public function setFirstname(string $firstname): UserInterface;

    /**
     * @return string
     */
    public function getLastname(): string;

    /**
     * @param string $lastname
     * @return UserInterface
     */
    public function setLastname(string $lastname): UserInterface;

    /**
     * @return string
     */
    public function getMail(): string;

    /**
     * @param string $mail
     * @return UserInterface
     */
    public function setMail(string $mail): UserInterface;

    /**
     * @return string
     */
    public function getLogin(): string;

    /**
     * @param string $login
     * @return UserInterface
     */
    public function setLogin(string $login): UserInterface;

    /**
     * @return string
     */
    public function getPhone(): string;

    /**
     * @param string|null $phone
     * @return UserInterface
     */
    public function setPhone(?string $phone): UserInterface;
}
