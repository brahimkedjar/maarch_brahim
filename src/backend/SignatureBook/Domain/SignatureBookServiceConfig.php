<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Service Config
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain;

/**
 *
 */
class SignatureBookServiceConfig
{
    private string $url;
    private UserWebService $userWebService;

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return SignatureBookServiceConfig
     */
    public function setUrl(string $url): SignatureBookServiceConfig
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return UserWebService
     */
    public function getUserWebService(): UserWebService
    {
        return $this->userWebService;
    }

    /**
     * @param UserWebService $userWebService
     *
     * @return SignatureBookServiceConfig
     */
    public function setUserWebService(UserWebService $userWebService): self
    {
        $this->userWebService = $userWebService;
        return $this;
    }
}
