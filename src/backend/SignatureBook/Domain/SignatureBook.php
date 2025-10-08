<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureBook class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain;

use JsonSerializable;

class SignatureBook implements JsonSerializable
{
    /**
     * @var SignatureBookResource[]
     */
    private array $resourcesToSign = [];
    /**
     * @var SignatureBookResource[]
     */
    private array $resourcesAttached = [];
    private bool $canSignResources = false;
    private bool $canUpdateResources = false;
    private bool $hasActiveWorkflow = false;
    private bool $isCurrentWorkflowUser = false;

    /**
     * @return SignatureBookResource[]
     */
    public function getResourcesToSign(): array
    {
        return $this->resourcesToSign;
    }

    /**
     * @param SignatureBookResource[] $resourcesToSign
     *
     * @return SignatureBook
     */
    public function setResourcesToSign(array $resourcesToSign): self
    {
        $this->resourcesToSign = $resourcesToSign;
        return $this;
    }

    /**
     * @param SignatureBookResource $resourceToSign
     *
     * @return SignatureBook
     */
    public function addResourceToSign(SignatureBookResource $resourceToSign): self
    {
        $this->resourcesToSign[] = $resourceToSign;
        return $this;
    }

    /**
     * @return SignatureBookResource[]
     */
    public function getResourcesAttached(): array
    {
        return $this->resourcesAttached;
    }

    /**
     * @param SignatureBookResource[] $resourcesAttached
     *
     * @return SignatureBook
     */
    public function setResourcesAttached(array $resourcesAttached): self
    {
        $this->resourcesAttached = $resourcesAttached;
        return $this;
    }

    /**
     * @param SignatureBookResource $resourceAttached
     *
     * @return SignatureBook
     */
    public function addResourceAttached(SignatureBookResource $resourceAttached): self
    {
        $this->resourcesAttached[] = $resourceAttached;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCanSignResources(): bool
    {
        return $this->canSignResources;
    }

    /**
     * @param bool $canSignResources
     */
    public function setCanSignResources(bool $canSignResources): self
    {
        $this->canSignResources = $canSignResources;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCanUpdateResources(): bool
    {
        return $this->canUpdateResources;
    }

    /**
     * @param bool $canUpdateResources
     *
     * @return SignatureBook
     */
    public function setCanUpdateResources(bool $canUpdateResources): self
    {
        $this->canUpdateResources = $canUpdateResources;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHasActiveWorkflow(): bool
    {
        return $this->hasActiveWorkflow;
    }

    /**
     * @param bool $hasActiveWorkflow
     *
     * @return SignatureBook
     */
    public function setHasActiveWorkflow(bool $hasActiveWorkflow): self
    {
        $this->hasActiveWorkflow = $hasActiveWorkflow;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCurrentWorkflowUser(): bool
    {
        return $this->isCurrentWorkflowUser;
    }

    /**
     * @param bool $isCurrentWorkflowUser
     *
     * @return SignatureBook
     */
    public function setIsCurrentWorkflowUser(bool $isCurrentWorkflowUser): self
    {
        $this->isCurrentWorkflowUser = $isCurrentWorkflowUser;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'resourcesToSign'       => $this->getResourcesToSign(),
            'resourcesAttached'     => $this->getResourcesAttached(),
            'canSignResources'      => $this->isCanSignResources(),
            'canUpdateResources'    => $this->isCanUpdateResources(),
            'hasActiveWorkflow'     => $this->isHasActiveWorkflow(),
            'isCurrentWorkflowUser' => $this->isCurrentWorkflowUser(),
        ];
    }
}
