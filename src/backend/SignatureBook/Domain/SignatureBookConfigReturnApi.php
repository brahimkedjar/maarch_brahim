<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureBookConfigReturnApi class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain;

use JsonSerializable;

class SignatureBookConfigReturnApi implements JsonSerializable
{
    private bool $isNewInternalParaph;
    private string $url;


    public function __construct()
    {
        $this->isNewInternalParaph = false;
        $this->url = '';
    }

    /**
     * @return bool
     */
    public function isNewInternalParaph(): bool
    {
        return $this->isNewInternalParaph;
    }

    /**
     * @param bool $isNewInternalParaph
     *
     * @return SignatureBookConfigReturnApi
     */
    public function setIsNewInternalParaph(bool $isNewInternalParaph): self
    {
        $this->isNewInternalParaph = $isNewInternalParaph;
        return $this;
    }

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
     * @return SignatureBookConfigReturnApi
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $array = [];

        $array['isNewInternalParaph'] = $this->isNewInternalParaph();

        if (!empty($this->getUrl())) {
            $array['url'] = $this->getUrl();
        }

        return $array;
    }
}
