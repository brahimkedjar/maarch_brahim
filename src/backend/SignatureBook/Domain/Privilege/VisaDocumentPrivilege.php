<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Visa document privilege
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Privilege;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;

class VisaDocumentPrivilege implements PrivilegeInterface
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'visa_documents';
    }
}
