<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Visa Workflow Repository Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface VisaWorkflowRepositoryInterface
{
    public function isWorkflowActiveByMainResource(MainResourceInterface $mainResource): bool;

    public function getCurrentStepUserByMainResource(MainResourceInterface $mainResource): ?UserInterface;
}
