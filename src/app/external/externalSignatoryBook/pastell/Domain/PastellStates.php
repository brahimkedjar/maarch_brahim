<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Pastell States
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\pastell\Domain;

class PastellStates
{
    private string $errorCode;
    private string $visaState;
    private string $signState;
    private string $refusedVisa;
    private string $refusedSign;

    /**
     * @param string $errorCode
     * @param string $visaState
     * @param string $signState
     * @param string $refusedVisa
     * @param string $refusedSign
     */
    public function __construct(string $errorCode, string $visaState, string $signState, string $refusedVisa, string $refusedSign)
    {
        $this->errorCode = $errorCode;
        $this->visaState = $visaState;
        $this->signState = $signState;
        $this->refusedVisa = $refusedVisa;
        $this->refusedSign = $refusedSign;
    }

    /**
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getVisaState(): string
    {
        return $this->visaState;
    }

    /**
     * @return string
     */
    public function getSignState(): string
    {
        return $this->signState;
    }

    /**
     * @return string
     */
    public function getRefusedVisa(): string
    {
        return $this->refusedVisa;
    }

    /**
     * @return string
     */
    public function getRefusedSign(): string
    {
        return $this->refusedSign;
    }
}
