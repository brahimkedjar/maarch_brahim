<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief EnvironnementInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Port;

interface EnvironmentInterface
{
    public function isDebug(): bool;

    public function isNewInternalParapheurEnabled(): bool;
}
