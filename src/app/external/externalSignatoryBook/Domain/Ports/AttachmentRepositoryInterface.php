<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment Repository interface
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\Domain\Ports;

interface AttachmentRepositoryInterface
{
    /**
     * @param int $id Attachment resId
     * @param string $externalId Document id from external resource
     * @return void
     */
    public function removeExternalLink(int $id, string $externalId): void;
}
