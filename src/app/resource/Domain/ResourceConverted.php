<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ResourceConverted class
 * @author dev@maarch.org
 */

namespace Resource\Domain;

class ResourceConverted implements HasDocserverFileInterface
{
    private int $id;
    private int $resId;
    private string $type;
    private int $version;
    private string $docserverId;
    private string $path;
    private string $filename;
    private ?string $fingerprint;
    private ?string $subject;

    public function __construct(
        int $id,
        int $resId,
        string $type,
        int $version,
        string $docserverId,
        string $path,
        string $filename,
        ?string $fingerprint
    ) {
        $this->id = $id;
        $this->resId = $resId;
        $this->type = $type;
        $this->version = $version;
        $this->docserverId = $docserverId;
        $this->path = $path;
        $this->filename = $filename;
        $this->fingerprint = $fingerprint;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getResId(): int
    {
        return $this->resId;
    }

    public function setResId(int $resId): void
    {
        $this->resId = $resId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function getDocserverId(): string
    {
        return $this->docserverId;
    }

    public function setDocserverId(string $docserverId): void
    {
        $this->docserverId = $docserverId;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }


    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(?string $fingerprint): void
    {
        $this->fingerprint = $fingerprint;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }
}
