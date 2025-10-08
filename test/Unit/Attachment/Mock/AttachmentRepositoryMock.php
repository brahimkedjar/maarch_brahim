<?php

declare(strict_types=1);

namespace MaarchCourrier\Tests\Unit\Attachment\Mock;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;

class AttachmentRepositoryMock implements AttachmentRepositoryInterface
{
    /**
     * @var AttachmentInterface[]
     */
    public array $attachmentsInSignatureBook = [];
    
    /**
     * @return AttachmentInterface[]
     */
    public function getAttachmentsInSignatureBookByMainResource(MainResourceInterface $mainResource): array
    {
        return $this->attachmentsInSignatureBook;
    }
}
