<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Pastell Data Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace ExternalSignatoryBook\pastell\Domain;

interface ResourceDataInterface
{
    /**
     * @param int $resId
     * @return array
     */
    public function getMainResourceData(int $resId): array;

    /**
     * @param int $resId
     * @return array
     */
    public function getIntegratedAttachmentsData(int $resId): array;

    /**
     * @return array
     */
    public function getAttachmentTypes(): array;

    /**
     * @param int $resId
     * @param string $type
     * @param string $signatoryUser
     * @return void
     */
    public function updateDocumentExternalStateSignatoryUser(int $resId, string $type, string $signatoryUser): void;
}
