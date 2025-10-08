<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\MainResource\Domain;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\DocumentStorage\Domain\Document;

class MainResource implements MainResourceInterface
{
    private int $resId;

    private ?string $subject;

    private UserInterface $typist;

    private ?string $chrono;

    private Integration $integration;

    private Document $document;

    public function getResId(): int
    {
        return $this->resId;
    }

    public function setResId(int $resId): MainResource
    {
        $this->resId = $resId;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): MainResource
    {
        $this->subject = $subject;
        return $this;
    }

    public function getTypist(): UserInterface
    {
        return $this->typist;
    }

    public function setTypist(UserInterface $typist): MainResource
    {
        $this->typist = $typist;
        return $this;
    }

    public function getChrono(): ?string
    {
        return $this->chrono;
    }

    public function setChrono(?string $chrono): MainResource
    {
        $this->chrono = $chrono;
        return $this;
    }

    public function getIntegration(): Integration
    {
        return $this->integration;
    }

    public function setIntegration(Integration $integration): MainResource
    {
        $this->integration = $integration;
        return $this;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function setDocument(Document $document): MainResource
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

    public function isInSignatureBook(): ?bool
    {
        return $this->integration->getInSignatureBook();
    }
}
