<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ResourceToSignRepositoryInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

interface ResourceToSignRepositoryInterface
{
    public function getResourceInformations(int $resId): array;

    public function getAttachmentInformations(int $resId): array;

    public function createSignVersionForResource(int $resId, array $storeInformations): void;

    public function updateAttachementStatus(int $resId): void;

    public function isResourceSigned(int $resId): bool;

    public function isAttachementSigned(int $resId): bool;

    public function checkConcordanceResIdAndResIdMaster(int $resId, int $resIdMaster): bool;

    public function setResourceExternalId(int $resId, int $parapheurDocumentId): void;

    public function setAttachmentExternalId(int $resId, int $parapheurDocumentId): void;
}
