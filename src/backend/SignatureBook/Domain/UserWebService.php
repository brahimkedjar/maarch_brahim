<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Signature
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain;

/**
 *  User WS to communicate between MC and MP API
 */
class UserWebService
{
    private string $login;
    private string $password = '';

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @param string $login
     *
     * @return UserWebService
     */
    public function setLogin(string $login): self
    {
        $this->login = $login;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return UserWebService
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }
}
