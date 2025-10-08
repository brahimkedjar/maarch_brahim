<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Convert Pdf Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentConversion\Infrastructure\Service;

use Convert\controllers\ConvertPdfController;
use MaarchCourrier\DocumentConversion\Domain\Port\ConvertPdfServiceInterface;
use Exception;

class ConvertPdfService implements ConvertPdfServiceInterface
{
    /**
     * @param string $extension
     *
     * @return bool
     * @throws Exception
     */
    public function canConvert(string $extension): bool
    {
        return ConvertPdfController::canConvert(['extension' => $extension]);
    }
}
