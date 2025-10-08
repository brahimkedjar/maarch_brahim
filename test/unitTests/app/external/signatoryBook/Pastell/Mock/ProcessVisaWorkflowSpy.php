<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock;

use ExternalSignatoryBook\pastell\Domain\ProcessVisaWorkflowInterface;

class ProcessVisaWorkflowSpy implements ProcessVisaWorkflowInterface
{
    public bool $processVisaWorkflowCalled = false;

    /**
     * @param int $resIdMaster
     * @param bool $processSignatory
     * @return void
     */
    public function processVisaWorkflow(int $resIdMaster, bool $processSignatory): void
    {
        $this->processVisaWorkflowCalled = true;
    }
}
