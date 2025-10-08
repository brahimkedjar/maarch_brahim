<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Main Document Repository Mock
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\DocumentConversion\Mock;

use MaarchCourrier\DocumentConversion\Domain\Port\SignatureMainDocumentRepositoryInterface;

class SignatureMainDocumentRepositoryMock implements SignatureMainDocumentRepositoryInterface
{
    public bool $mainDocumentIsSigned = false;
    /**
     * @inheritDoc
     */
    public function isMainDocumentSigned(int $resId): bool
    {
        return $this->mainDocumentIsSigned;
    }
}
