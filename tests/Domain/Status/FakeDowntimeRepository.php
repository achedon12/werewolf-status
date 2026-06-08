<?php

declare(strict_types=1);

namespace Domain\Status;

use App\Domain\Status\DowntimeRepository;
use JetBrains\PhpStorm\ArrayShape;
use Override;

final class FakeDowntimeRepository implements DowntimeRepository
{
    public int $startDowntimeCalls = 0;
    public int $endDowntimeCalls = 0;

    public function startDowntime(int $endpointId, ?int $httpCode, ?string $reason): ?array
    {
        $this->startDowntimeCalls++;

        return [
            'id' => 1,
            'endpoint_id' => $endpointId,
            'down_at' => '2026-01-01 12:00:00',
            'up_at' => null,
            'http_code' => $httpCode,
            'reason' => $reason,
            'discord_down_notified_at' => null,
            'discord_up_notified_at' => null,
        ];
    }

    public function endDowntime(int $endpointId): ?array
    {
        $this->endDowntimeCalls++;

        return [
            'id' => 1,
            'endpoint_id' => $endpointId,
            'down_at' => '2026-01-01 12:00:00',
            'up_at' => '2026-01-01 12:05:00',
            'http_code' => 500,
            'reason' => 'Service down',
            'discord_down_notified_at' => null,
            'discord_up_notified_at' => null,
        ];
    }

    public function markDiscordDownNotified(int $downtimeId): void
    {
    }

    public function markDiscordUpNotified(int $downtimeId): void
    {
    }

    public function getStats(int $endpointId, int $periodHours = 48, int $slots = 24): array
    {
        return [
            [
                'status' => 'up',
                'downtime_seconds' => 0,
            ],
        ];
    }

    public function isCurrentlyDown(int $endpointId): bool
    {
        return false;
    }
}
