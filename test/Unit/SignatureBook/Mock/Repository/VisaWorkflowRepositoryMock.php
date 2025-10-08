<?php

declare(strict_types=1);

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\Repository;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;
use MaarchCourrier\User\Domain\User;

class VisaWorkflowRepositoryMock implements VisaWorkflowRepositoryInterface
{
    public bool $isActiveWorkflow = false;
    public bool $return = true;

    public function isWorkflowActiveByMainResource(MainResourceInterface $mainResource): bool
    {
        return $this->isActiveWorkflow;
    }

    public function getCurrentStepUserByMainResource(MainResourceInterface $mainResource): ?UserInterface
    {
        return User::createFromArray(['id' => $mainResource->getTypist()->getId()]);
    }

}
