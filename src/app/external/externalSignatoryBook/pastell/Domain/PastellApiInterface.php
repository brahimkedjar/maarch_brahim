<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Pastell API Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace ExternalSignatoryBook\pastell\Domain;

interface PastellApiInterface
{
    /**
     * @param PastellConfig $config
     * @return array
     */
    public function getVersion(PastellConfig $config): array;

    /**
     * @param PastellConfig $config
     * @return array
     */
    public function getEntity(PastellConfig $config): array;

    /**
     * @param PastellConfig $config
     * @return array
     */
    public function getConnector(PastellConfig $config): array;

    /**
     * @param PastellConfig $config
     * @return array
     */
    public function getFolderType(PastellConfig $config): array;

    /**
     * @param PastellConfig $config
     * @return array
     */
    public function getIparapheurType(PastellConfig $config): array;

    /**
     * @param PastellConfig $config
     * @return array
     */
    public function createFolder(PastellConfig $config): array;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array
     */
    public function getIparapheurSousType(PastellConfig $config, string $idFolder): array;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @param string $title
     * @param string $sousType
     * @return array
     */
    public function editFolder(PastellConfig $config, string $idFolder, string $title, string $sousType): array;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @param string $filePath
     * @return array
     */
    public function uploadMainFile(PastellConfig $config, string $idFolder, string $filePath): array;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @param string $filePath
     * @param int $nbAttachments
     * @return array
     */
    public function uploadAttachmentFile(PastellConfig $config, string $idFolder, string $filePath, int $nbAttachments): array;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array
     */
    public function getFolderDetail(PastellConfig $config, string $idFolder): array;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return object|array
     */
    public function getXmlDetail(PastellConfig $config, string $idFolder): object;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array
     */
    public function downloadFile(PastellConfig $config, string $idFolder): array;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return bool
     */
    public function verificationIParapheur(PastellConfig $config, string $idFolder): bool;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array
     */
    public function orientation(PastellConfig $config, string $idFolder): array;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return bool
     */
    public function sendIparapheur(PastellConfig $config, string $idFolder): bool;

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @return array
     */
    public function deleteFolder(PastellConfig $config, string $idFolder): array;
}
