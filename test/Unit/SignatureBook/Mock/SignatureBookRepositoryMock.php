<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   SignatureBookRepositoryMock
 * @author  dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;
use Resource\Domain\Resource;

class SignatureBookRepositoryMock implements SignatureBookRepositoryInterface
{
    public bool $hasActiveWorkflow = true;
    public bool $isInSignatureBookBasket = true;
    public bool $canUpdateResourcesInSignatureBook = true;
    public int $workflowUserId = 19;
    public bool $isCurrentWorkflowUser = true;

    /**
     * @param Resource $resource
     *
     * @return SignatureBookResource[]
     */
    public function getIncomingMainResource(Resource $resource): array
    {
        $resourcesToSign = [];

        $resourceToSign = (new SignatureBookResource())
            ->setResId($resource->getResId())
            ->setTitle("HellDivers 2 : How’d you like the TASTE of FREEDOM?")
            ->setChrono("MAARCH/2024A/34")
            ->setCreatorId($resource->getTypist())
            ->setType('main_document')
            ->setTypeLabel(_MAIN_DOCUMENT);
        $resourcesToSign[] = $resourceToSign;

        return $resourcesToSign;
    }

    /**
     * @param Resource $resource
     *
     * @return SignatureBookResource[]
     */
    public function getIncomingAttachments(Resource $resource): array
    {
        $resourcesToSign = [];

        $resourceToSign = (new SignatureBookResource())
            ->setResId($resource->getResId())
            ->setTitle("HellDivers 2 : How’d you like the TASTE of FREEDOM?")
            ->setChrono("MAARCH/2024A/34")
            ->setType('response_project')
            ->setTypeLabel("Projet de réponse");
        $resourcesToSign[] = $resourceToSign;

        return $resourcesToSign;
    }

    /**
     * @param Resource $resource
     *
     * @return SignatureBookResource[]
     */
    public function getAttachments(Resource $resource): array
    {
        $resourcesAttached = [];

        $resourceAttached = (new SignatureBookResource())
            ->setResId(101)
            ->setResIdMaster($resource->getResId())
            ->setTitle("HellDivers 2 : How’d you like the TASTE of FREEDOM?")
            ->setCreatorId($resource->getTypist())
            ->setType('main_document')
            ->setTypeLabel(_MAIN_DOCUMENT);
        $resourcesAttached[] = $resourceAttached;

        return $resourcesAttached;
    }

    public function canUpdateResourcesInSignatureBook(MainResourceInterface $mainResource, UserInterface $user): bool
    {
        return $this->canUpdateResourcesInSignatureBook;
    }

    public function doesMainResourceHasActiveWorkflow(Resource $resource): bool
    {
        return $this->hasActiveWorkflow;
    }

    public function getWorkflowUserIdByCurrentStep(Resource $resource): ?int
    {
        if ($this->isCurrentWorkflowUser) {
            return $this->workflowUserId;
        }
        return 2;
    }

    public function isMainResourceInSignatureBookBasket(MainResourceInterface $mainResource, UserInterface $user): bool
    {
        return $this->isInSignatureBookBasket;
    }
}
