<?php

declare(strict_types=1);

namespace MaarchCourrier\Tests\Unit\MainResource\Mock;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\MainResource\Domain\MainResource;

class MainResourceRepositoryMock implements MainResourceRepositoryInterface
{
    public ?MainResource $mainResource = null;

    public function getMainResourceByResId(int $resId): ?MainResourceInterface
    {
        return $this->mainResource;
    }
}
