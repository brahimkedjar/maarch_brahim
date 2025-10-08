<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment repository infrastructure
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\Infrastructure;

use Attachment\models\AttachmentModel;
use ExternalSignatoryBook\Domain\Ports\AttachmentRepositoryInterface;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    public function removeExternalLink(int $id, string $externalId): void
    {
        AttachmentModel::removeExternalLink(['resId' => $id, 'externalId' => $externalId]);
    }
}
