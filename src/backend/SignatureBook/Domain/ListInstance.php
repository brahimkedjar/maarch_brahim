<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ListInstance class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain;

class ListInstance
{
    private int $listInstanceId;
    private int $resId;
    private int $sequence;
    private int $itemId;
    private string $itemType;
    private bool $requestedSignature;
    private ?\DateTimeInterface $processDate;

    public function getListInstanceId(): int
    {
        return $this->listInstanceId;
    }

    public function setListInstanceId(int $listInstanceId): ListInstance
    {
        $this->listInstanceId = $listInstanceId;
        return $this;
    }

    public function getResId(): int
    {
        return $this->resId;
    }

    public function setResId(int $resId): ListInstance
    {
        $this->resId = $resId;
        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): ListInstance
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): ListInstance
    {
        $this->itemId = $itemId;
        return $this;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): ListInstance
    {
        $this->itemType = $itemType;
        return $this;
    }

    public function isRequestedSignature(): bool
    {
        return $this->requestedSignature;
    }

    public function setRequestedSignature(bool $requestedSignature): ListInstance
    {
        $this->requestedSignature = $requestedSignature;
        return $this;
    }

    public function getProcessDate(): ?\DateTimeInterface
    {
        return $this->processDate;
    }

    public function setProcessDate(?\DateTimeInterface $processDate): ListInstance
    {
        $this->processDate = $processDate;
        return $this;
    }
}
