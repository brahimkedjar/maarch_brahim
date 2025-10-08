<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve from Pastell factory
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\pastell\Infrastructure;

use ExternalSignatoryBook\Infrastructure\DocumentLinkFactory;
use ExternalSignatoryBook\pastell\Application\ParseIParapheurLog;
use ExternalSignatoryBook\pastell\Application\PastellConfigurationCheck;
use ExternalSignatoryBook\pastell\Application\RetrieveFromPastell;

class RetrieveFromPastellFactory
{
    /**
     * @return RetrieveFromPastell
     */
    public static function create(): RetrieveFromPastell
    {
        $pastellApi = new PastellApi();
        $pastellConfig = new PastellXmlConfig();
        $pastellConfigCheck = new PastellConfigurationCheck($pastellApi, $pastellConfig);
        $processVisaWorkflow = new ProcessVisaWorkflow();
        $parseIParapheurLog = new ParseIParapheurLog($pastellApi, $pastellConfig, $pastellConfigCheck, $processVisaWorkflow);
        $resourceData = new ResourceDataDb();
        $historyRepository = new HistoryRepository();
        $documentLink = DocumentLinkFactory::createDocumentLink();

        return new RetrieveFromPastell($pastellApi, $pastellConfig, $pastellConfigCheck, $parseIParapheurLog, $resourceData, $historyRepository, $documentLink);
    }
}
