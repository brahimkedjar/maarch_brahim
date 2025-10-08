<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   Convert Pdf Service Mock
 * @author  dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\DocumentConversion\Mock;

use MaarchCourrier\DocumentConversion\Domain\Port\ConvertPdfServiceInterface;

class ConvertPdfServiceMock implements ConvertPdfServiceInterface
{
    public bool $isConvertable = true;

    /**
     * @param string $extension
     *
     * @return bool
     */
    public function canConvert(string $extension): bool
    {
        return $this->isConvertable;
    }
}
