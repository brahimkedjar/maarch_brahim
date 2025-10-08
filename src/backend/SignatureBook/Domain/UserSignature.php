<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief User Signature
* @author dev@maarch.org
*/

namespace MaarchCourrier\SignatureBook\Domain;

use JsonSerializable;

class UserSignature implements JsonSerializable
{
    private int $id;
    private int $userSerialId;
    private string $signatureLabel;
    private string $signaturePath;
    private string $signatureFileName;
    private string $fingerprint;

    /**
     * @param array $array
     * @return UserSignature
     */
    public static function createFromArray(array $array = []): UserSignature
    {
        $userSignature = new UserSignature();

        $userSignature
            ->setId($array['id'] ?? 0)
            ->setUserSerialId($array['user_serial_id'] ?? 0)
            ->setSignatureLabel($array['signature_label'] ?? '')
            ->setSignaturePath($array['signature_path'] ?? '')
            ->setSignatureFileName($array['signature_file_name'] ?? '')
            ->setFingerprint($array['fingerprint'] ?? '');

        return $userSignature;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserSerialId(): int
    {
        return $this->userSerialId;
    }

    /**
     * @param int $userSerialId
     */
    public function setUserSerialId(int $userSerialId): self
    {
        $this->userSerialId = $userSerialId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSignatureLabel(): string
    {
        return $this->signatureLabel;
    }

    /**
     * @param string $signatureLabel
     */
    public function setSignatureLabel(string $signatureLabel): self
    {
        $this->signatureLabel = $signatureLabel;
        return $this;
    }

    /**
     * @return string
     */
    public function getSignaturePath(): string
    {
        return $this->signaturePath;
    }

    /**
     * @param string $signaturePath
     */
    public function setSignaturePath(string $signaturePath): self
    {
        $this->signaturePath = $signaturePath;
        return $this;
    }

    /**
     * @return string
     */
    public function getSignatureFileName(): string
    {
        return $this->signatureFileName;
    }

    /**
     * @param string $signatureFileName
     */
    public function setSignatureFileName(string $signatureFileName): self
    {
        $this->signatureFileName = $signatureFileName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    /**
     * @param string $fingerprint
     */
    public function setFingerprint(string $fingerprint): self
    {
        $this->fingerprint = $fingerprint;
        return $this;
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
        if (!empty($this->getSignatureLabel())) {
            $array['signatureLabel'] = $this->getSignatureLabel();
        }
        if (!empty($this->getSignaturePath())) {
            $array['signaturePath'] = $this->getSignaturePath();
        }
        if (!empty($this->getSignatureFileName())) {
            $array['signatureFileName'] = $this->getSignatureFileName();
        }
        if (!empty($this->getFingerprint())) {
            $array['fingerprint'] = $this->getFingerprint();
        }

        return $array;
    }
}
