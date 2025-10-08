<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve from Pastell
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\pastell\Application;

use ExternalSignatoryBook\Application\DocumentLink;
use ExternalSignatoryBook\pastell\Domain\PastellApiInterface;
use ExternalSignatoryBook\pastell\Domain\PastellConfig;
use ExternalSignatoryBook\pastell\Domain\PastellConfigInterface;
use ExternalSignatoryBook\pastell\Domain\ResourceDataInterface;
use ExternalSignatoryBook\pastell\Domain\HistoryRepositoryInterface;
use Throwable;

class RetrieveFromPastell
{
    private PastellApiInterface $pastellApi;
    private PastellConfigInterface $pastellConfig;
    private PastellConfigurationCheck $pastellConfigCheck;
    private ParseIParapheurLog $parseIParapheurLog;
    private PastellConfig $config;
    private ResourceDataInterface $resourceData;
    private HistoryRepositoryInterface $historyRepository;
    private DocumentLink $documentLink;

    /**
     * @param PastellApiInterface $pastellApi
     * @param PastellConfigInterface $pastellConfig
     * @param PastellConfigurationCheck $pastellConfigCheck
     * @param ParseIParapheurLog $parseIParapheurLog
     * @param ResourceDataInterface $resourceData
     * @param HistoryRepositoryInterface $historyRepository
     * @param DocumentLink $documentLink
     */
    public function __construct(
        PastellApiInterface $pastellApi,
        PastellConfigInterface $pastellConfig,
        PastellConfigurationCheck $pastellConfigCheck,
        ParseIParapheurLog $parseIParapheurLog,
        ResourceDataInterface $resourceData,
        HistoryRepositoryInterface $historyRepository,
        DocumentLink $documentLink
    ) {
        $this->pastellApi = $pastellApi;
        $this->pastellConfig = $pastellConfig;
        $this->pastellConfigCheck = $pastellConfigCheck;
        $this->parseIParapheurLog = $parseIParapheurLog;
        $this->config = $this->pastellConfig->getPastellConfig();
        $this->resourceData = $resourceData;
        $this->historyRepository = $historyRepository;
        $this->documentLink = $documentLink;
    }

    /**
     * @param array $idsToRetrieve
     * @param string $documentType
     * @return array|string[]
     */
    public function retrieve(array $idsToRetrieve, string $documentType): array
    {
        if (!$this->pastellConfigCheck->checkPastellConfig()) {
            return ['success' => [], 'error' => 'Cannot retrieve resources from pastell : pastell configuration is invalid'];
        }
        $errors = [];

        foreach ($idsToRetrieve as $key => $value) {
            $info = $this->pastellApi->getFolderDetail($this->config, $value['external_id']);
            if (!empty($info['error'])) {
                $errors[$key] = 'Error when getting folder detail : ' . $info['error'];

                $infosError = (is_array($info['error'])) ? implode('-', $info['error']) : $info['error'];
                $this->historyRepository->addLogInHistory($value['res_id_master'] ?? $value['res_id'], 'Error when getting folder detail : ' . $infosError);

                if (
                    $info['code'] == 404 &&
                    $info['error'] == "Le document {$value['external_id']} n'appartient pas à l'entité {$this->config->getEntity()}"
                ) {
                    try {
                        $type = $documentType == 'resLetterbox' ? 'resource' : 'attachment';
                        $title = $documentType == 'resLetterbox' ? $value['subject'] : $value['title'];
                        $this->documentLink->removeExternalLink($value['res_id'], $title, $type, $value['external_id']);
                    } catch (Throwable $th) {
                        $errors[$key] = "[SCRIPT] Failed to remove document link: MaarchCourrier docId {$value['res_id']}, document type $type, parapheur docId {$value['external_id']}";
                        $errors[$key] .= ". Error: {$th->getMessage()}.";
                    }
                }

                unset($idsToRetrieve[$key]);
            } else {
                if (in_array('verif-iparapheur', $info['actionPossibles'] ?? [])) {
                    $verif = $this->pastellApi->verificationIParapheur($this->config, $value['external_id']);
                    if ($verif !== true) {
                        $errors[$key] = 'Action "verif-iparapheur" failed';

                        $this->historyRepository->addLogInHistory($value['res_id_master'] ?? $value['res_id'], 'Action "verif-iparapheur" failed');
                        unset($idsToRetrieve[$key]);
                        continue;
                    }
                }
                // Need res_id_master for parseLogIparapheur and res_id for updateDocument (for attachments and main document)
                $resId = $value['res_id_master'] ?? $value['res_id'];
                $result = $this->parseIParapheurLog->parseLogIparapheur($resId, $value['external_id']);
                $this->resourceData->updateDocumentExternalStateSignatoryUser($value['res_id'], $documentType == 'resLetterbox' ? 'resource' : 'attachment', $result['signatory'] ?? '');

                if (!empty($result['error'])) {
                    $errors[$key] = $result['error'];

                    $resultError = (is_array($result['error'])) ? implode('-', $result['error']) : $result['error'];
                    $this->historyRepository->addLogInHistory($resId, $resultError);
                    unset($idsToRetrieve[$key]);
                    continue;
                }

                $idsToRetrieve[$key] = array_merge($value, $result);

                // Deletion is automatic if postAction in conf is suppression
                $postAction = $this->pastellConfig->getPastellConfig()->getPostAction();
                if (
                    $postAction == 'suppression' &&
                    ($result['status'] == 'validated' || $result['status'] == 'refused')
                ) {
                    $deleteFolderResult = $this->pastellApi->deleteFolder($this->config, $value['external_id']);
                    if (!empty($deleteFolderResult['error'])) {
                        $errors[$key] = $deleteFolderResult['error'];
                        $deleteError = (is_array($deleteFolderResult['error'])) ? implode('-', $deleteFolderResult['error']) : $deleteFolderResult['error'];
                        $this->historyRepository->addLogInHistory($resId, $deleteError);
                        unset($idsToRetrieve[$key]);
                    }
                }
            }
        }

        return ['success' => $idsToRetrieve, 'error' => $errors];
    }
}
