<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Integration Class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\MainResource\Domain;

class Integration
{
    private ?bool $inSignatureBook;

    public function createFromArray(array $values): Integration
    {
        return (new Integration())
            ->setInSignatureBook($values['inSignatureBook'] ?? false);
    }

    public function getInSignatureBook(): ?bool
    {
        return $this->inSignatureBook;
    }

    public function setInSignatureBook(?bool $inSignatureBook): Integration
    {
        $this->inSignatureBook = $inSignatureBook;
        return $this;
    }
}
