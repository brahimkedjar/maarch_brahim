<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock;

use ExternalSignatoryBook\pastell\Domain\PastellApiInterface;
use ExternalSignatoryBook\pastell\Domain\PastellConfig;
use stdClass;

class PastellApiMock implements PastellApiInterface
{
    public array $version = [];
    public array $entity = ['192', '193', '813'];
    public array $connector = ['193', '776', '952'];
    public array $flux = ['ls-document-pdf', 'test', 'not-pdf'];
    public array $iParapheurType = ['XELIANS COURRIER', 'TEST', 'PASTELL'];
    public bool $doesFolderExist = true;
    public array $folder = ['idFolder' => 'hfqvhv'];
    public array $iParapheurSousType = ['courrier', 'réponse au citoyen'];
    public array $documentDetails = [
        'info'            => [],
        'data'            => [],
        'actionPossibles' => ['verif-iparapheur'],
        'lastAction'      => []
    ];
    public array $mainFile = [];
    public array $dataFolder = [
        'libelle',
        'courrier',
        'XELIANS COURRIER',
        true
    ];
    public string $sousTypeUsed = '';
    public array $documentsDownload = [];
    public object $journalXml;
    public array $orientation = [];
    public string $verificationIparapheurFailedId = '';
    public bool $sendIparapheur = true;
    public array $uploadedAnnexes = [];
    public array $uploadAnnexError = [];
    public array $deletedFolder = [];

    /**
     * @param PastellConfig $config
     * @return string[]
     */
    public function getVersion(PastellConfig $config): array
    {
        return $this->version;
    }

    /**
     * @param PastellConfig $config
     * @return array
     */
    public function getEntity(PastellConfig $config): array
    {
        return $this->entity;
    }

    /**
     * @param PastellConfig $config
     * @return array
     */
    public function getConnector(PastellConfig $config): array
    {
        return $this->connector;
    }

    /**
     * @param PastellConfig $config
     * @return array
     */
    public function getFolderType(PastellConfig $config): array
    {
        return $this->flux;
    }

    /**
     * @param PastellConfig $config
     * @return array
     */
    public function getIparapheurType(PastellConfig $config): array
    {
        return $this->iParapheurType;
    }

    /**
     * @param PastellConfig $config
     * @return array|string[]
     */
    public function createFolder(PastellConfig $config): array
    {
        return $this->folder;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array|string[]
     */
    public function getIparapheurSousType(PastellConfig $config, string $idFolder): array
    {
        return $this->iParapheurSousType;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @param string $title
     * @param string $sousType
     * @return array|string[]
     */
    public function editFolder(PastellConfig $config, string $idFolder, string $title, string $sousType): array
    {
        $this->sousTypeUsed = $sousType;

        return $this->dataFolder;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @param string $filePath
     * @return array
     */
    public function uploadMainFile(PastellConfig $config, string $idFolder, string $filePath): array
    {
        return $this->mainFile;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array
     */
    public function getFolderDetail(PastellConfig $config, string $idFolder): array
    {
        if (!$this->doesFolderExist) {
            return ["code" => 404, "error" => "Le document blabla n'appartient pas à l'entité {$config->getEntity()}"];
        }
        if ($idFolder === 'blabla') {
            return ["code" => 400, "error" => 'An error occurred !'];
        }

        return $this->documentDetails;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return object
     */
    public function getXmlDetail(PastellConfig $config, string $idFolder): object
    {
        return $this->journalXml ?? new stdClass();
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array
     */
    public function downloadFile(PastellConfig $config, string $idFolder): array
    {
        return $this->documentsDownload;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return bool
     */
    public function verificationIParapheur(PastellConfig $config, string $idFolder): bool
    {
        return $this->verificationIparapheurFailedId !== $idFolder;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array
     */
    public function orientation(PastellConfig $config, string $idFolder): array
    {
        return $this->orientation;
    }

    /**
     * @param PastellConfig|null $config
     * @param string $idFolder
     * @return bool
     */
    public function sendIparapheur(PastellConfig $config, string $idFolder): bool
    {
        return $this->sendIparapheur;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @param string $filePath
     * @param int $nbAttachments
     * @return array
     */
    public function uploadAttachmentFile(PastellConfig $config, string $idFolder, string $filePath, int $nbAttachments): array
    {
        if (empty($this->uploadAnnexError)) {
            $this->uploadedAnnexes[] = ['nb' => $nbAttachments, 'filePath' => $filePath];
        }

        return $this->uploadAnnexError;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array
     */
    public function deleteFolder(PastellConfig $config, string $idFolder): array
    {
        return $this->deletedFolder;
    }
}
