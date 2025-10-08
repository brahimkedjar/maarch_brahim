<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Document Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentConversion\Infrastructure\Repository;

use Convert\models\AdrModel;
use MaarchCourrier\DocumentConversion\Domain\Port\SignatureMainDocumentRepositoryInterface;

class SignatureMainDocumentRepository implements SignatureMainDocumentRepositoryInterface
{
    public function isMainDocumentSigned(int $resId): bool
    {
        $signedDoc = AdrModel::getDocuments([
            'select' => ['*'],
            'where'  => ['res_id = ?', 'type = ?'],
            'data'   => [$resId, 'SIGN'],
            'limit'  => 1
        ]);

        return !empty($signedDoc[0]);
    }
}
