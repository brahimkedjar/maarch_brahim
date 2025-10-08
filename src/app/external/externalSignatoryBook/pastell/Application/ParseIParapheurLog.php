<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Parse iParapheur Log
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace ExternalSignatoryBook\pastell\Application;

use ExternalSignatoryBook\pastell\Domain\PastellApiInterface;
use ExternalSignatoryBook\pastell\Domain\PastellConfig;
use ExternalSignatoryBook\pastell\Domain\PastellConfigInterface;
use ExternalSignatoryBook\pastell\Domain\PastellStates;
use ExternalSignatoryBook\pastell\Domain\ProcessVisaWorkflowInterface;

class ParseIParapheurLog
{
    private PastellApiInterface $pastellApi;
    private PastellConfigInterface $pastellConfig;
    private PastellConfigurationCheck $pastellConfigCheck;
    private ProcessVisaWorkflowInterface $processVisaWorkflow;
    private PastellConfig $config;
    private PastellStates $pastellStates;

    public function __construct(
        PastellApiInterface $pastellApi,
        PastellConfigInterface $pastellConfig,
        PastellConfigurationCheck $pastellConfigCheck,
        ProcessVisaWorkflowInterface $processVisaWorkflow
    ) {
        $this->pastellApi = $pastellApi;
        $this->pastellConfig = $pastellConfig;
        $this->pastellConfigCheck = $pastellConfigCheck;
        $this->processVisaWorkflow = $processVisaWorkflow;

        $this->config = $this->pastellConfig->getPastellConfig();
        $this->pastellStates = $this->pastellConfig->getPastellStates();
    }

    /**
     * @param int $resId
     * @param string $idFolder
     * @return array|string[]
     */
    public function parseLogIparapheur(int $resId, string $idFolder): array
    {
        $return = [];
        $iParapheurHistory = $this->pastellApi->getXmlDetail($this->config, $idFolder);
        if (!empty($iParapheurHistory->error)) {
            return ['error' => $iParapheurHistory->error];
        }

        if ($iParapheurHistory->MessageRetour->codeRetour == $this->pastellStates->getErrorCode()) {
            return ['error' => 'Log KO in iParapheur : [' . $iParapheurHistory->MessageRetour->severite . '] ' . $iParapheurHistory->MessageRetour->message];
        }
        foreach ($iParapheurHistory->LogDossier->LogDossier as $historyLog) {
            $status = $historyLog->status;
            if ($status == $this->pastellStates->getSignState()) {
                $return = $this->handleValidate($resId, $idFolder, true);
                $return['signatory'] = $historyLog->nom ?? '';
                break;
            } elseif ($status == $this->pastellStates->getVisaState()) {
                $return = $this->handleValidate($resId, $idFolder, false);
                $return['signatory'] = $historyLog->nom ?? '';
                break;
            } elseif ($status == $this->pastellStates->getRefusedSign() || $status == $this->pastellStates->getRefusedVisa()) {
                $return = $this->handleRefused((string)$historyLog->nom ?? '', (string)$historyLog->annotation ?? '');
                $return['signatory'] = (string)$historyLog->nom ?? '';
                break;
            }
        }
        if (empty($return)) {
            $return = ['status' => 'waiting'];
        }

        return $return;
    }

    /**
     * @param int $res
     * @param string $idFolder
     * @param bool $signed
     * @return array
     */
    public function handleValidate(int $res, string $idFolder, bool $signed): array
    {

        $file = $this->pastellApi->downloadFile($this->config, $idFolder);
        if (!empty($file['error'])) {
            return ['error' => $file['error']];
        }

        if ($signed) {
            $this->processVisaWorkflow->processVisaWorkflow($res, true);
        }

        return [
            'status'      => 'validated',
            'format'      => 'pdf',
            'encodedFile' => $file['encodedFile']
        ];
    }

    /**
     * @param string $nom
     * @param string $annotation
     * @return string[]
     */
    public function handleRefused(string $nom, string $annotation): array
    {
        $noteContent = $nom . ' : ' . $annotation;

        return [
            'status'  => 'refused',
            'content' => $noteContent
        ];
    }
}
