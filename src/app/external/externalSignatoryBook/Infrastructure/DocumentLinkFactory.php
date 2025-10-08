<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Document link factory
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\Infrastructure;

use ExternalSignatoryBook\Application\DocumentLink;

class DocumentLinkFactory
{
    /**
     * @return DocumentLink
     */
    public static function createDocumentLink(): DocumentLink
    {
        $userRepository       = new UserRepository();
        $resourceRepository   = new ResourceRepository();
        $attachmentRepository = new AttachmentRepository();
        $historyRepository    = new HistoryRepository();

        return new DocumentLink($userRepository, $resourceRepository, $attachmentRepository, $historyRepository);
    }
}
