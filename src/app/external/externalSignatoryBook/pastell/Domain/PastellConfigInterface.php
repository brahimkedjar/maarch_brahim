<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Pastell Config Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace ExternalSignatoryBook\pastell\Domain;

interface PastellConfigInterface
{
    /**
     * @return PastellConfig|null
     */
    public function getPastellConfig(): ?PastellConfig;

    /**
     * @return PastellStates|null
     */
    public function getPastellStates(): ?PastellStates;
}
