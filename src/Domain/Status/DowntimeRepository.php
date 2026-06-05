<?php

declare(strict_types=1);

namespace App\Domain\Status;

interface DowntimeRepository
{
    public function startDowntime(
        string $service,
        ?int $httpCode = null,
        ?string $reason = null
    ): void;

    public function endDowntime(string $service): void;

    public function isCurrentlyDown(string $service): bool;

    public function getDailyStats(string $service): array;
}