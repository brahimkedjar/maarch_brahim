<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Pastell XML Config
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\pastell\Infrastructure;

use Exception;
use ExternalSignatoryBook\pastell\Domain\PastellConfig;
use ExternalSignatoryBook\pastell\Domain\PastellConfigInterface;
use ExternalSignatoryBook\pastell\Domain\PastellStates;
use SrcCore\models\CoreConfigModel;

class PastellXmlConfig implements PastellConfigInterface
{
    /**
     * @return PastellConfig|null
     * @throws Exception
     */
    public function getPastellConfig(): ?PastellConfig
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        $pastellConfig = null;
        if (!empty($loadedXml)) {
            $PastellConfig = $loadedXml->xpath('//signatoryBook[id=\'pastell\']')[0] ?? null;
            if ($PastellConfig) {
                $pastellConfig = new PastellConfig(
                    (string)$PastellConfig->url ?? null,
                    (string)$PastellConfig->login ?? null,
                    (string)$PastellConfig->password ?? null,
                    (int)$PastellConfig->entityId ?? null,
                    (int)$PastellConfig->connectorId ?? null,
                    (string)$PastellConfig->documentType ?? null,
                    (string)$PastellConfig->iParapheurType ?? null,
                    (string)$PastellConfig->iParapheurSousType ?? null,
                    (string)$PastellConfig->postAction ?? null
                );
            }
        }
        return $pastellConfig;
    }

    /**
     * @return PastellStates|null
     * @throws Exception
     */
    public function getPastellStates(): ?PastellStates
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        $pastellState = null;
        if (!empty($loadedXml)) {
            $pastellState = $loadedXml->xpath('//signatoryBook[id=\'pastell\']')[0] ?? null;
            if ($pastellState) {
                $pastellState = new PastellStates(
                    (string)$pastellState->errorCode ?? null,
                    (string)$pastellState->visaState ?? null,
                    (string)$pastellState->signState ?? null,
                    (string)$pastellState->refusedVisa ?? null,
                    (string)$pastellState->refusedSign ?? null,
                );
            }
        }
        return $pastellState;
    }
}
