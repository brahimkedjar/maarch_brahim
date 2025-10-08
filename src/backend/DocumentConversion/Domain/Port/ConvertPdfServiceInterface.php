<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Convert Pdf Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentConversion\Domain\Port;

interface ConvertPdfServiceInterface
{
    /**
     * @param string $extension
     *
     * @return bool
     */
    public function canConvert(string $extension): bool;
}
