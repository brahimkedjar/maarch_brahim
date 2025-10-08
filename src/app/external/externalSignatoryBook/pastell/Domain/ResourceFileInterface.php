<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource file Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace ExternalSignatoryBook\pastell\Domain;

interface ResourceFileInterface
{
    /**
     * @param int $resId
     * @return string
     */
    public function getMainResourceFilePath(int $resId): string;

    /**
     * @param int $resId
     * @param string $fingerprint
     * @return string
     */
    public function getAttachmentFilePath(int $resId, string $fingerprint): ?string;
}
