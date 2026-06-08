<?php

declare(strict_types=1);

namespace App\Domain\Status;

interface DowntimeRepository
{
    public function startDowntime(
        int $endpointId,
        ?int $httpCode,
        ?string $reason
    ): ?array;

    public function endDowntime(int $endpointId): ?array;

    public function isCurrentlyDown(int $endpointId): bool;

    public function getStats(
        int $endpointId,
        int $periodHours = 48,
        int $slotCount = 24
    ): array;

    public function markDiscordDownNotified(int $downtimeId): void;

    public function markDiscordUpNotified(int $downtimeId): void;
}
