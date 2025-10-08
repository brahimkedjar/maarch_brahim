<?php

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock;

use ExternalSignatoryBook\pastell\Domain\HistoryRepositoryInterface;

class HistoryRepositorySpy implements HistoryRepositoryInterface
{
    public bool $historyAdded = false;

    public function addLogInHistory(int $id, string $message): void
    {
        $this->historyAdded = true;
    }
}
