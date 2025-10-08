<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   ResourceToSignRepository mock
 * @author  dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\Webhook;

use MaarchCourrier\SignatureBook\Domain\Port\ResourceToSignRepositoryInterface;

class ResourceToSignRepositoryMock implements ResourceToSignRepositoryInterface
{
    public bool $signedVersionCreate = false;
    public bool $resourceUpdated = false;
    public bool $attachmentUpdated = false;
    public bool $resourceNotExists = false;
    public bool $attachmentNotExists = false;
    public bool $resourceAlreadySigned = false;
    public bool $resIdConcordingWithResIdMaster = true;

    public array $resourceInformations = [
        'version'     => 1,
        'external_id' => '{"internalParapheur":20}'
    ];

    public array $attachmentInformations = [
        'res_id_master'  => 100,
        'title'          => 'PDF_Reponse_blocsignature',
        'typist'         => 19,
        'identifier'     => 'MAARCH/2024D/1000',
        'recipient_id'   => 6,
        'recipient_type' => 'contact',
        'format'         => 'pdf',
        'external_id'    => '{"internalParapheur":20}'
    ];

    public function getResourceInformations(int $resId): array
    {
        if ($this->resourceNotExists) {
            return [];
        }
        return $this->resourceInformations;
    }

    public function getAttachmentInformations(int $resId): array
    {
        if ($this->attachmentNotExists) {
            return [];
        }

        return $this->attachmentInformations;
    }

    public function createSignVersionForResource(int $resId, array $storeInformations): void
    {
        $this->signedVersionCreate = true;
    }

    public function updateAttachementStatus(int $resId): void
    {
        $this->attachmentUpdated = true;
    }

    public function isResourceSigned(int $resId): bool
    {
        return $this->resourceAlreadySigned;
    }

    public function isAttachementSigned(int $resId): bool
    {
        return $this->resourceAlreadySigned;
    }

    public function checkConcordanceResIdAndResIdMaster(int $resId, int $resIdMaster): bool
    {
        return $this->resIdConcordingWithResIdMaster;
    }

    public function setResourceExternalId(int $resId, int $parapheurDocumentId): void
    {
        $this->resourceUpdated = true;
    }

    public function setAttachmentExternalId(int $resId, int $parapheurDocumentId): void
    {
        $this->attachmentUpdated = true;
    }
}
