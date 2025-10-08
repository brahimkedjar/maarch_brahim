<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Attachment\Domain;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\DocumentStorage\Domain\Document;

class Attachment implements AttachmentInterface
{
    private int $resId;
    private MainResourceInterface $mainResource;
    private ?string $title;
    private ?string $chrono;
    private UserInterface $typist;
    private int $relation;
    private AttachmentType $type;
    private Document $document;

    public function getResId(): int
    {
        return $this->resId;
    }

    public function setResId(int $resId): Attachment
    {
        $this->resId = $resId;
        return $this;
    }

    public function getMainResource(): MainResourceInterface
    {
        return $this->mainResource;
    }

    public function setMainResource(MainResourceInterface $mainResource): Attachment
    {
        $this->mainResource = $mainResource;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): Attachment
    {
        $this->title = $title;
        return $this;
    }

    public function getChrono(): ?string
    {
        return $this->chrono;
    }

    public function setChrono(?string $chrono): Attachment
    {
        $this->chrono = $chrono;
        return $this;
    }

    public function getTypist(): UserInterface
    {
        return $this->typist;
    }

    public function setTypist(UserInterface $typist): Attachment
    {
        $this->typist = $typist;
        return $this;
    }

    public function getRelation(): int
    {
        return $this->relation;
    }

    public function setRelation(int $relation): Attachment
    {
        $this->relation = $relation;
        return $this;
    }

    public function getType(): AttachmentType
    {
        return $this->type;
    }

    public function setType(AttachmentType $type): Attachment
    {
        $this->type = $type;
        return $this;
    }

    public function getTypeIdentifier(): string
    {
        return $this->type->getType();
    }

    public function getTypeLabel(): string
    {
        return $this->type->getLabel();
    }

    public function isSignable(): bool
    {
        return $this->type->isSignable();
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function setDocument(Document $document): Attachment
    {
        $this->document = $document;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->document->getFileName();
    }

    public function getFileFormat(): ?string
    {
        return $this->document->getFileExtension();
    }
}
