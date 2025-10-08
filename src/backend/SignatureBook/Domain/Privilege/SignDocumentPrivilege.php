<?php

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Domain\Privilege;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;

class SignDocumentPrivilege implements PrivilegeInterface
{
    public function getName(): string
    {
        return 'sign_document';
    }
}
