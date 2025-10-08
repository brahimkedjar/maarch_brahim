<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource\Port;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface MainResourceInterface
{
    public function getResId(): int;

    public function getSubject(): ?string;

    public function getTypist(): UserInterface;

    public function getChrono(): ?string;

    public function getFilename(): ?string;
    public function getFileFormat(): ?string;

    public function isInSignatureBook(): ?bool;
}
