<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Document Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentConversion\Domain\Port;

interface SignatureMainDocumentRepositoryInterface
{
    /**
     * @param int $resId
     *
     * @return bool
     */
    public function isMainDocumentSigned(int $resId): bool;
}
