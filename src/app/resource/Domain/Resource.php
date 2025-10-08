<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource class
 * @author dev@maarch.org
 */

namespace Resource\Domain;

class Resource implements HasDocserverFileInterface
{
    private int $resId;
    private ?string $subject;
    private ?int $typeId;
    private ?string $format;
    private int $typist;
    private string $creationDate;
    private ?string $modificationDate;
    private ?string $docDate;
    private ?string $docserverId;
    private ?string $path;
    private ?string $filename;
    private ?string $fingerprint;
    private ?int $filesize;
    private ?string $status;
    private ?string $destination;
    private ?int $workBatch;
    private ?string $origin;
    private ?string $priority;
    private ?string $policyId;
    private ?string $cycleId;
    private ?string $referenceNumber;
    private ?string $initiator;
    private ?int $destUser;
    private ?string $lockerUserId;
    private ?string $lockerTime;
    private ?string $confidentiality;
    private ?string $fulltextResult;
    private ?string $externalReference;
    private array $externalId;
    private array $externalState;
    private ?string $departureDate;
    private ?string $opinionLimitDate;
    private ?string $barcode;
    private ?string $categoryId;
    private ?string $altIdentifier;
    private ?string $admissionDate;
    private ?string $processLimitDate;
    private ?string $closingDate;
    private ?string $flagAlarm1;
    private ?string $flagAlarm2;
    private int $modelId;
    private int $version;
    private array $integrations;
    private ?array $customFields;
    private array $linkedResources;
    private bool $retentionFrozen;
    private ?bool $binding;

    /**
     * @param array $array
     * @return Resource
     */
    public static function createFromArray(array $array = []): Resource
    {
        $resource = new Resource();

        $resource->setResId($array['res_id'] ?? 0);
        $resource->setSubject($array['subject'] ?? '');
        $resource->setTypeId($array['type_id'] ?? null);
        $resource->setFormat($array['format'] ?? null);
        $resource->setTypist($array['typist'] ?? 0);
        $resource->setCreationDate($array['creation_date'] ?? '');
        $resource->setModificationDate($array['modification_date'] ?? null);
        $resource->setDocDate($array['doc_date'] ?? null);
        $resource->setDocserverId($array['docserver_id'] ?? null);
        $resource->setPath($array['path'] ?? null);
        $resource->setFilename($array['filename'] ?? null);
        $resource->setFingerprint($array['fingerprint'] ?? null);
        $resource->setFilesize($array['filesize'] ?? null);
        $resource->setStatus($array['status'] ?? null);
        $resource->setDestination($array['destination'] ?? null);
        $resource->setWorkBatch($array['work_batch'] ?? null);
        $resource->setOrigin($array['origin'] ?? null);
        $resource->setPriority($array['priority'] ?? null);
        $resource->setPolicyId($array['policy_id'] ?? null);
        $resource->setCycleId($array['cycle_id'] ?? null);
        $resource->setReferenceNumber($array['reference_number'] ?? null);
        $resource->setInitiator($array['initiator'] ?? null);
        $resource->setDestUser($array['dest_user'] ?? null);
        $resource->setLockerUserId($array['locker_user_id'] ?? null);
        $resource->setLockerTime($array['locker_time'] ?? null);
        $resource->setConfidentiality($array['confidentiality'] ?? null);
        $resource->setFulltextResult($array['fulltext_result'] ?? null);
        $resource->setExternalReference($array['external_reference'] ?? null);
        $resource->setExternalId(json_decode($array['external_id'] ?? '{}', true));
        $resource->setExternalState(json_decode($array['external_state'] ?? '{}', true));
        $resource->setDepartureDate($array['departure_date'] ?? null);
        $resource->setOpinionLimitDate($array['opinion_limit_date'] ?? null);
        $resource->setBarcode($array['barcode'] ?? null);
        $resource->setCategoryId($array['category_id'] ?? null);
        $resource->setAltIdentifier($array['alt_identifier'] ?? null);
        $resource->setAdmissionDate($array['admission_date'] ?? null);
        $resource->setProcessLimitDate($array['process_limit_date'] ?? null);
        $resource->setClosingDate($array['closing_date'] ?? null);
        $resource->setFlagAlarm1($array['flag_alarm1'] ?? null);
        $resource->setFlagAlarm2($array['flag_alarm2'] ?? null);
        $resource->setModelId($array['model_id'] ?? 0);
        $resource->setVersion($array['version'] ?? 0);
        $resource->setIntegrations(json_decode($array['integrations'] ?? '{}', true));
        $resource->setCustomFields(json_decode($array['custom_fields'] ?? '{}', true));
        $resource->setLinkedResources(json_decode($array['linked_resources'] ?? '{}', true));
        $resource->setRetentionFrozen($array['retention_frozen'] ?? false);
        $resource->setBinding($array['binding'] ?? null);

        return $resource;
    }

    // Getters
    public function getResId(): int
    {
        return $this->resId;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getTypeId(): ?int
    {
        return $this->typeId;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getTypist(): int
    {
        return $this->typist;
    }

    public function getCreationDate(): string
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?string
    {
        return $this->modificationDate;
    }

    public function getDocDate(): ?string
    {
        return $this->docDate;
    }

    public function getDocserverId(): ?string
    {
        return $this->docserverId;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function getFilesize(): ?int
    {
        return $this->filesize;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function getWorkBatch(): ?int
    {
        return $this->workBatch;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function getPolicyId(): ?string
    {
        return $this->policyId;
    }

    public function getCycleId(): ?string
    {
        return $this->cycleId;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function getInitiator(): ?string
    {
        return $this->initiator;
    }

    public function getDestUser(): ?int
    {
        return $this->destUser;
    }

    public function getLockerUserId(): ?string
    {
        return $this->lockerUserId;
    }

    public function getLockerTime(): ?string
    {
        return $this->lockerTime;
    }

    public function getConfidentiality(): ?string
    {
        return $this->confidentiality;
    }

    public function getFulltextResult(): ?string
    {
        return $this->fulltextResult;
    }

    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }

    public function getExternalId(): array
    {
        return $this->externalId;
    }

    public function getExternalState(): array
    {
        return $this->externalState;
    }

    public function getDepartureDate(): ?string
    {
        return $this->departureDate;
    }

    public function getOpinionLimitDate(): ?string
    {
        return $this->opinionLimitDate;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function getAltIdentifier(): ?string
    {
        return $this->altIdentifier;
    }

    public function getAdmissionDate(): ?string
    {
        return $this->admissionDate;
    }

    public function getProcessLimitDate(): ?string
    {
        return $this->processLimitDate;
    }

    public function getClosingDate(): ?string
    {
        return $this->closingDate;
    }

    public function getFlagAlarm1(): ?string
    {
        return $this->flagAlarm1;
    }

    public function getFlagAlarm2(): ?string
    {
        return $this->flagAlarm2;
    }

    public function getModelId(): int
    {
        return $this->modelId;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getIntegrations(): array
    {
        return $this->integrations;
    }

    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    public function getLinkedResources(): array
    {
        return $this->linkedResources;
    }

    public function getRetentionFrozen(): bool
    {
        return $this->retentionFrozen;
    }

    public function getBinding(): ?bool
    {
        return $this->binding;
    }

    // Setters
    public function setResId(int $resId): void
    {
        $this->resId = $resId;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function setTypeId(?int $typeId): void
    {
        $this->typeId = $typeId;
    }

    public function setFormat(?string $format): void
    {
        $this->format = $format;
    }

    public function setTypist(int $typist): void
    {
        $this->typist = $typist;
    }

    public function setCreationDate(string $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function setModificationDate(?string $modificationDate): void
    {
        $this->modificationDate = $modificationDate;
    }

    public function setDocDate(?string $docDate): void
    {
        $this->docDate = $docDate;
    }

    public function setDocserverId(?string $docserverId): void
    {
        $this->docserverId = $docserverId;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
    }

    public function setFingerprint(?string $fingerprint): void
    {
        $this->fingerprint = $fingerprint;
    }

    public function setFilesize(?int $filesize): void
    {
        $this->filesize = $filesize;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function setDestination(?string $destination): void
    {
        $this->destination = $destination;
    }

    public function setWorkBatch(?int $workBatch): void
    {
        $this->workBatch = $workBatch;
    }

    public function setOrigin(?string $origin): void
    {
        $this->origin = $origin;
    }

    public function setPriority(?string $priority): void
    {
        $this->priority = $priority;
    }

    public function setPolicyId(?string $policyId): void
    {
        $this->policyId = $policyId;
    }

    public function setCycleId(?string $cycleId): void
    {
        $this->cycleId = $cycleId;
    }

    public function setReferenceNumber(?string $referenceNumber): void
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function setInitiator(?string $initiator): void
    {
        $this->initiator = $initiator;
    }

    public function setDestUser(?int $destUser): void
    {
        $this->destUser = $destUser;
    }

    public function setLockerUserId(?string $lockerUserId): void
    {
        $this->lockerUserId = $lockerUserId;
    }

    public function setLockerTime(?string $lockerTime): void
    {
        $this->lockerTime = $lockerTime;
    }

    public function setConfidentiality(?string $confidentiality): void
    {
        $this->confidentiality = $confidentiality;
    }

    public function setFulltextResult(?string $fulltextResult): void
    {
        $this->fulltextResult = $fulltextResult;
    }

    public function setExternalReference(?string $externalReference): void
    {
        $this->externalReference = $externalReference;
    }

    public function setExternalId(array $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function setExternalState(array $externalState): void
    {
        $this->externalState = $externalState;
    }

    public function setDepartureDate(?string $departureDate): void
    {
        $this->departureDate = $departureDate;
    }

    public function setOpinionLimitDate(?string $opinionLimitDate): void
    {
        $this->opinionLimitDate = $opinionLimitDate;
    }

    public function setBarcode(?string $barcode): void
    {
        $this->barcode = $barcode;
    }

    public function setCategoryId(?string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function setAltIdentifier(?string $altIdentifier): void
    {
        $this->altIdentifier = $altIdentifier;
    }

    public function setAdmissionDate(?string $admissionDate): void
    {
        $this->admissionDate = $admissionDate;
    }

    public function setProcessLimitDate(?string $processLimitDate): void
    {
        $this->processLimitDate = $processLimitDate;
    }

    public function setClosingDate(?string $closingDate): void
    {
        $this->closingDate = $closingDate;
    }

    public function setFlagAlarm1(?string $flagAlarm1): void
    {
        $this->flagAlarm1 = $flagAlarm1;
    }

    public function setFlagAlarm2(?string $flagAlarm2): void
    {
        $this->flagAlarm2 = $flagAlarm2;
    }

    public function setModelId(int $modelId): void
    {
        $this->modelId = $modelId;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function setIntegrations(array $integrations): void
    {
        $this->integrations = $integrations;
    }

    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }

    public function setLinkedResources(array $linkedResources): void
    {
        $this->linkedResources = $linkedResources;
    }

    public function setRetentionFrozen(bool $retentionFrozen): void
    {
        $this->retentionFrozen = $retentionFrozen;
    }

    public function setBinding(?bool $binding): void
    {
        $this->binding = $binding;
    }
}
