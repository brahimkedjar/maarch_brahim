<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief StoreSignedResourceService class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure;

use Docserver\controllers\DocserverController;
use MaarchCourrier\SignatureBook\Domain\Port\StoreSignedResourceServiceInterface;
use MaarchCourrier\SignatureBook\Domain\SignedResource;
use Resource\controllers\StoreController;

class StoreSignedResourceService implements StoreSignedResourceServiceInterface
{
    public function storeResource(SignedResource $signedResource): array
    {
        return DocserverController::storeResourceOnDocServer([
            'collId'          => 'letterbox_coll',
            'docserverTypeId' => 'DOC',
            'encodedResource' => $signedResource->getEncodedContent(),
            'format'          => 'pdf'
        ]);
    }

    public function storeAttachement(SignedResource $signedResource, array $attachment): int|array
    {
        $data = [
            'title'                    => $attachment['title'],
            'encodedFile'              => $signedResource->getEncodedContent(),
            'status'                   => 'TRA',
            'format'                   => 'pdf',
            'typist'                   => $attachment['typist'],
            'resIdMaster'              => $attachment['res_id_master'],
            'chrono'                   => $attachment['identifier'],
            'type'                     => 'signed_response',
            'originId'                 => $signedResource->getResIdSigned(),
            'recipientId'              => $attachment['recipient_id'],
            'recipientType'            => $attachment['recipient_type'],
            'inSignatureBook'          => true,
            'signatory_user_serial_id' => $signedResource->getUserSerialId()
        ];

        $id = StoreController::storeAttachment($data);
        if (!empty($id['errors'])) {
            return ['errors' => $id['errors']];
        }

        return $id;
    }
}
