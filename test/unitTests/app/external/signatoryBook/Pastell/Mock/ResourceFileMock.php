<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

declare(strict_types=1);

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock;

use ExternalSignatoryBook\pastell\Domain\ResourceFileInterface;

class ResourceFileMock implements ResourceFileInterface
{
    public string $adrMainInfo = 'toto.pdf';
    public string $attachmentFilePath = '';

    /**
     * @param int $resId
     * @return string
     */
    public function getMainResourceFilePath(int $resId): string
    {
        return $this->adrMainInfo;
    }

    /**
     * @param int $resId
     * @param string $fingerprint
     * @return string
     */
    public function getAttachmentFilePath(int $resId, string $fingerprint): string
    {
        return $this->attachmentFilePath;
    }
}
