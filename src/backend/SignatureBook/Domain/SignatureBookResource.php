<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureBookResource class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain;

use JsonSerializable;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;

class SignatureBookResource implements JsonSerializable
{
    private int $resId;
    private ?int $resIdMaster = null;
    private string $title;
    private string $chrono;
    private int $creatorId;
    private ?int $signedResId = null;
    private string $type;
    private string $typeLabel;
    private bool $isConverted = false;
    private bool $canModify = false;
    private bool $canDelete = false;


    public static function createFromMainResource(MainResourceInterface $mainResource): SignatureBookResource
    {
        return (new SignatureBookResource())
            ->setResId($mainResource->getResId())
            ->setTitle($mainResource->getSubject())
            ->setCreatorId($mainResource->getTypist()->getId())
            ->setChrono($mainResource->getChrono())
            ->setType('main_document')
            ->setTypeLabel('Document Principal');
    }

    public static function createFromAttachment(AttachmentInterface $attachment): SignatureBookResource
    {
        return (new SignatureBookResource())
            ->setResId($attachment->getResId())
            ->setResIdMaster($attachment->getMainResource()->getResId())
            ->setTitle($attachment->getTitle())
            ->setChrono($attachment->getChrono())
            ->setCreatorId($attachment->getTypist()->getId())
            ->setSignedResId($attachment->getRelation())
            ->setType($attachment->getTypeIdentifier())
            ->setTypeLabel($attachment->getTypeLabel());
    }

    public function getResId(): int
    {
        return $this->resId;
    }

    /**
     * @param int $resId
     *
     * @return SignatureBookResource
     */
    public function setResId(int $resId): self
    {
        $this->resId = $resId;
        return $this;
    }

    /**
     * @return ?int
     */
    public function getResIdMaster(): ?int
    {
        return $this->resIdMaster;
    }

    /**
     * @param ?int $resIdMaster
     *
     * @return SignatureBookResource
     */
    public function setResIdMaster(?int $resIdMaster): self
    {
        $this->resIdMaster = $resIdMaster;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return SignatureBookResource
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getChrono(): string
    {
        return $this->chrono;
    }

    /**
     * @param string $chrono
     *
     * @return SignatureBookResource
     */
    public function setChrono(string $chrono): self
    {
        $this->chrono = $chrono;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreatorId(): int
    {
        return $this->creatorId;
    }

    /**
     * @param int $creatorId
     *
     * @return SignatureBookResource
     */
    public function setCreatorId(int $creatorId): self
    {
        $this->creatorId = $creatorId;
        return $this;
    }

    /**
     * @return ?int
     */
    public function getSignedResId(): ?int
    {
        return $this->signedResId;
    }

    /**
     * @param ?int $signedResId
     *
     * @return SignatureBookResource
     */
    public function setSignedResId(?int $signedResId): self
    {
        $this->signedResId = $signedResId;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return SignatureBookResource
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeLabel(): string
    {
        return $this->typeLabel;
    }

    /**
     * @param string $typeLabel
     *
     * @return SignatureBookResource
     */
    public function setTypeLabel(string $typeLabel): self
    {
        $this->typeLabel = $typeLabel;
        return $this;
    }

    /**
     * @return bool
     */
    public function isConverted(): bool
    {
        return $this->isConverted;
    }

    /**
     * @param bool $isConverted
     *
     * @return SignatureBookResource
     */
    public function setIsConverted(bool $isConverted): self
    {
        $this->isConverted = $isConverted;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCanModify(): bool
    {
        return $this->canModify;
    }

    /**
     * @param bool $canModify
     *
     * @return SignatureBookResource
     */
    public function setCanModify(bool $canModify): self
    {
        $this->canModify = $canModify;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCanDelete(): bool
    {
        return $this->canDelete;
    }

    /**
     * @param bool $canDelete
     *
     * @return SignatureBookResource
     */
    public function setCanDelete(bool $canDelete): self
    {
        $this->canDelete = $canDelete;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'resId'       => $this->getResId(),
            'resIdMaster' => $this->getResIdMaster(),
            'title'       => $this->getTitle(),
            'chrono'      => $this->getChrono(),
            'signedResId' => $this->getSignedResId(),
            'type'        => $this->getType(),
            'typeLabel'   => $this->getTypeLabel(),
            'isConverted' => $this->isConverted(),
            'canModify'   => $this->isCanModify(),
            'canDelete'   => $this->isCanDelete()
        ];
    }
}
