<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Service Config Loader Interface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

interface SignatureServiceConfigLoaderInterface
{
    public function getSignatureServiceConfig(): ?SignatureBookServiceConfig;
}
