<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock;

use ExternalSignatoryBook\pastell\Domain\PastellConfig;
use ExternalSignatoryBook\pastell\Domain\PastellConfigInterface;
use ExternalSignatoryBook\pastell\Domain\PastellStates;

class PastellConfigMock implements PastellConfigInterface
{
    public ?PastellConfig $pastellConfig = null;
    public ?PastellStates $pastellStates = null;

    public function __construct()
    {
        $this->pastellConfig = new PastellConfig(
            'testurl',
            'toto',
            'toto123',
            193,
            776,
            'ls-document-pdf',
            'XELIANS COURRIER',
            'courrier',
            'suppression'
        );
        $this->pastellStates = new PastellStates(
            'KO',
            'VisaOK',
            'CachetOK',
            'RejetVisa',
            'RejetCachet'
        );
    }

    /**
     * @return PastellConfig|null
     */
    public function getPastellConfig(): ?PastellConfig
    {
        return $this->pastellConfig;
    }

    /**
     * @return PastellStates|null
     */
    public function getPastellStates(): ?PastellStates
    {
        return $this->pastellStates;
    }
}
