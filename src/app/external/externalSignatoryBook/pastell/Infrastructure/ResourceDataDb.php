<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource data DB
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace ExternalSignatoryBook\pastell\Infrastructure;

use Attachment\models\AttachmentModel;
use Attachment\models\AttachmentTypeModel;
use Exception;
use ExternalSignatoryBook\pastell\Domain\ResourceDataInterface;
use Resource\models\ResModel;

class ResourceDataDb implements ResourceDataInterface
{
    /**
     * @param int $resId
     * @return array
     * @throws Exception
     */
    public function getMainResourceData(int $resId): array
    {
        return ResModel::get([
            'select' => ['res_id', 'path', 'filename', 'docserver_id', 'format', 'category_id', 'external_id', 'integrations', 'subject'],
            'where'  => ['res_id = ?'],
            'data'   => [$resId]
        ])[0];
    }

    /**
     * @param int $resId
     * @return array
     * @throws Exception
     */
    public function getIntegratedAttachmentsData(int $resId): array
    {
        return AttachmentModel::get([
            'select' => ['res_id', 'docserver_id', 'path', 'filename', 'format', 'attachment_type', 'fingerprint', 'title'],
            'where'  => ['res_id_master = ?', 'attachment_type not in (?)', "status NOT IN ('DEL','OBS', 'FRZ', 'TMP', 'SEND_MASS')", "in_signature_book = 'true'"],
            'data'   => [$resId, ['signed_response']]
        ]);
    }

    /**
     * @return array
     */
    public function getAttachmentTypes(): array
    {
        $attachmentTypes = AttachmentTypeModel::get(['select' => ['type_id', 'signable']]);
        return array_column($attachmentTypes, 'signable', 'type_id');
    }

    /**
     * @param int $resId
     * @param string $type
     * @param string $signatoryUser
     * @return void
     * @throws Exception
     */
    public function updateDocumentExternalStateSignatoryUser(int $resId, string $type, string $signatoryUser): void
    {
        if ($type == 'resource') {
            ResModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$resId],
                'postSet' => [
                    'external_state' => "jsonb_set(external_state::jsonb, '{signatoryUser}', '\"$signatoryUser\"')"
                ]
            ]);
        } else {
            AttachmentModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$resId],
                'postSet' => [
                    'external_state' => "jsonb_set(external_state::jsonb, '{signatoryUser}', '\"$signatoryUser\"')"
                ]
            ]);
        }
    }
}
