<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief AttachmentType class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Attachment\Domain;

class AttachmentType
{
    private string $type;

    private string $label;

    private bool $signable;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): AttachmentType
    {
        $this->type = $type;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): AttachmentType
    {
        $this->label = $label;
        return $this;
    }

    public function isSignable(): bool
    {
        return $this->signable;
    }

    public function setSignable(bool $signable): AttachmentType
    {
        $this->signable = $signable;
        return $this;
    }
}
