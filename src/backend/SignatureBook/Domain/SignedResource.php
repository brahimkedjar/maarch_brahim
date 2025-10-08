<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignedResource class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain;

use DateTimeInterface;
use JsonSerializable;

class SignedResource implements JsonSerializable
{
    private int $id = -1;
    private int $userSerialId = -1;
    private int $resIdSigned = -1;
    private ?int $resIdMaster = null;
    private string $status = "";
    private ?string $messageStatus = null;

    private ?DateTimeInterface $signatureDate = null;
    private ?string $encodedContent = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserSerialId(): int
    {
        return $this->userSerialId;
    }

    public function setUserSerialId(int $userSerialId): void
    {
        $this->userSerialId = $userSerialId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getSignatureDate(): DateTimeInterface|null
    {
        return $this->signatureDate;
    }

    public function getMessageStatus(): ?string
    {
        return $this->messageStatus;
    }

    public function setMessageStatus(?string $messageStatus): void
    {
        $this->messageStatus = $messageStatus;
    }

    public function setSignatureDate(?DateTimeInterface $signatureDate): void
    {
        $this->signatureDate = $signatureDate;
    }

    public function getEncodedContent(): ?string
    {
        return $this->encodedContent;
    }

    public function setEncodedContent(?string $encodedContent): void
    {
        $this->encodedContent = $encodedContent;
    }


    public function getResIdSigned(): int
    {
        return $this->resIdSigned;
    }

    public function setResIdSigned(int $resIdSigned): void
    {
        $this->resIdSigned = $resIdSigned;
    }

    public function getResIdMaster(): ?int
    {
        return $this->resIdMaster;
    }

    public function setResIdMaster(?int $resIdMaster): void
    {
        $this->resIdMaster = $resIdMaster;
    }

    public function jsonSerialize(): array
    {
        $array = [];

        if ($this->getId() > 0) {
            $array['id'] = $this->getId();
        }
        if (!empty($this->getUserSerialId())) {
            $array['userSerialId'] = $this->getUserSerialId();
        }

        if (!empty($this->getStatus())) {
            $array['status'] = $this->getStatus();
        }

        if (!empty($this->getSignatureDate())) {
            $array['signatureDate'] = $this->getSignatureDate();
        }

        if (!empty($this->getEncodedContent())) {
            $array['encodedContent'] = $this->getEncodedContent();
        }

        if (!empty($this->getResIdSigned())) {
            $array['resIdSigned'] = $this->getResIdSigned();
        }

        if (!empty($this->getResIdMaster())) {
            $array['resIdMaster'] = $this->getResIdMaster();
        }

        return $array;
    }
}
