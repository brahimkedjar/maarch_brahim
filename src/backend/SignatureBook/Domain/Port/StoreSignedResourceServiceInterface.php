<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief StoreSignedResourceServiceInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\SignatureBook\Domain\SignedResource;

interface StoreSignedResourceServiceInterface
{
    public function storeResource(SignedResource $signedResource): array;

    public function storeAttachement(SignedResource $signedResource, array $attachment): int|array;
}
