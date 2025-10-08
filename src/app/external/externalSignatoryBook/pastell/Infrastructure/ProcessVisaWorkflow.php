<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Process Visa Workflow
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\pastell\Infrastructure;

use ExternalSignatoryBook\controllers\IParapheurController;
use ExternalSignatoryBook\pastell\Domain\ProcessVisaWorkflowInterface;

class ProcessVisaWorkflow implements ProcessVisaWorkflowInterface
{
    /**
     * @param int $resIdMaster
     * @param bool $processSignatory
     * @return void
     */
    public function processVisaWorkflow(int $resIdMaster, bool $processSignatory): void
    {
        IParapheurController::processVisaWorkflow(
            ['res_id_master' => $resIdMaster, 'processSignatory' => $processSignatory]
        );
    }
}
