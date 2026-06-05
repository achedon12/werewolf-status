<?php

declare(strict_types=1);

namespace App\Domain\Status;

interface DowntimeRepository
{
    public function startDowntime(
        int $endpointId,
        ?int $httpCode,
        ?string $reason
    ): void;

    public function endDowntime(int $endpointId): void;

    public function isCurrentlyDown(int $endpointId): bool;

    public function getStats(
        int $endpointId,
        int $periodHours = 48,
        int $slotCount = 24
    ): array;
}