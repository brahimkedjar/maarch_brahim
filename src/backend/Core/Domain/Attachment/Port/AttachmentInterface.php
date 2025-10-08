<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Attachment\Port;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface AttachmentInterface
{
    public function getResId(): int;

    public function getMainResource(): MainResourceInterface;

    public function getTitle(): ?string;

    public function getChrono(): ?string;

    public function getTypist(): UserInterface;

    public function getRelation(): int;

    public function getTypeIdentifier(): string;

    public function getTypeLabel(): string;

    public function isSignable(): bool;
    public function getFilename(): ?string;
    public function getFileFormat(): ?string;
}
