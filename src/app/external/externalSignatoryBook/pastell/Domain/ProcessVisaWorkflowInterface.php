<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Pastell Visa Workflow Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace ExternalSignatoryBook\pastell\Domain;

interface ProcessVisaWorkflowInterface
{
    /**
     * @param int $resIdMaster
     * @param bool $processSignatory
     * @return void
     */
    public function processVisaWorkflow(int $resIdMaster, bool $processSignatory): void;
}
