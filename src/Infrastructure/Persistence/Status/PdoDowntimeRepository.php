<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Status;

use App\Domain\Status\DowntimeRepository;
use PDO;

final class PdoDowntimeRepository implements DowntimeRepository
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function startDowntime(
        int $endpointId,
        ?int $httpCode,
        ?string $reason
    ): void {
        if ($this->isCurrentlyDown($endpointId)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO downtimes (
            endpoint_id,
            down_at,
            up_at,
            http_code,
            reason,
            created_at
        ) VALUES (
            :endpoint_id,
            NOW(),
            NULL,
            :http_code,
            :reason,
            NOW()
        )'
        );

        $stmt->execute([
            'endpoint_id' => $endpointId,
            'http_code' => $httpCode,
            'reason' => $reason,
        ]);
    }

    public function endDowntime(int $endpointId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE downtimes
             SET up_at = NOW()
             WHERE endpoint_id = :endpoint_id
             AND up_at IS NULL'
        );

        $stmt->execute([
            'endpoint_id' => $endpointId,
        ]);
    }

    public function isCurrentlyDown(int $endpointId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS count
         FROM downtimes
         WHERE endpoint_id = :endpoint_id
         AND up_at IS NULL'
        );

        $stmt->execute([
            'endpoint_id' => $endpointId,
        ]);

        $row = $stmt->fetch();

        return isset($row['count']) && (int) $row['count'] > 0;
    }

    public function getStats(
        int $endpointId,
        int $periodHours = 48,
        int $slotCount = 24
    ): array {
        $now = time();

        $periodSeconds = $periodHours * 3600;
        $periodStart = $now - $periodSeconds;
        $slotDuration = (int) ($periodSeconds / $slotCount);

        $stmt = $this->pdo->prepare(
            'SELECT *
         FROM downtimes
         WHERE endpoint_id = :endpoint_id
         AND down_at < :period_end
         AND (up_at IS NULL OR up_at > :period_start)
         ORDER BY down_at ASC'
        );

        $stmt->execute([
            'endpoint_id' => $endpointId,
            'period_start' => date('Y-m-d H:i:s', $periodStart),
            'period_end' => date('Y-m-d H:i:s', $now),
        ]);

        $events = $stmt->fetchAll();

        $slots = [];
        $totalDowntimeSeconds = 0;

        for ($i = 0; $i < $slotCount; $i++) {
            $slotStart = $periodStart + ($i * $slotDuration);
            $slotEnd = $slotStart + $slotDuration;

            $downtimeSecondsInSlot = 0;
            $startedAgo = null;

            foreach ($events as $event) {
                $downAt = strtotime((string) $event['down_at']);
                $upAt = $event['up_at'] !== null
                    ? strtotime((string) $event['up_at'])
                    : $now;

                if ($downAt === false || $upAt === false) {
                    continue;
                }

                $overlapStart = max($slotStart, $downAt);
                $overlapEnd = min($slotEnd, $upAt);

                if ($overlapStart < $overlapEnd) {
                    $downtimeSecondsInSlot += $overlapEnd - $overlapStart;
                }

                if (
                    $event['up_at'] === null
                    && $downAt >= $slotStart
                    && $downAt < $slotEnd
                ) {
                    $startedAgo = $this->formatAgo($downAt, $now);
                }
            }

            $downtimeSecondsInSlot = min($downtimeSecondsInSlot, $slotDuration);
            $totalDowntimeSeconds += $downtimeSecondsInSlot;

            $status = match (true) {
                $downtimeSecondsInSlot === 0 => 'up',
                $downtimeSecondsInSlot >= $slotDuration => 'down',
                default => 'partial',
            };

            $slots[] = [
                'index' => $i,
                'status' => $status,
                'downtime_seconds' => $downtimeSecondsInSlot,
                'uptime_seconds' => $slotDuration - $downtimeSecondsInSlot,
                'from' => date('Y-m-d H:i:s', $slotStart),
                'to' => date('Y-m-d H:i:s', $slotEnd),
                'label' => $this->formatSlotLabel($slotStart, $slotEnd, $now),
                'started_ago' => $startedAgo,
            ];
        }

        $uptimePercent = round(
            (($periodSeconds - $totalDowntimeSeconds) / $periodSeconds) * 100,
            2
        );

        return [
            'slots' => $slots,
            'down_slots' => count(array_filter(
                $slots,
                fn (array $slot): bool => $slot['status'] === 'down'
            )),
            'partial_slots' => count(array_filter(
                $slots,
                fn (array $slot): bool => $slot['status'] === 'partial'
            )),
            'downtime_seconds' => $totalDowntimeSeconds,
            'uptime_percent' => $uptimePercent,
            'period_hours' => $periodHours,
            'slot_count' => $slotCount,
            'slot_duration_seconds' => $slotDuration,
            'period_start' => date('Y-m-d H:i:s', $periodStart),
            'period_end' => date('Y-m-d H:i:s', $now),
        ];
    }

    private function formatAgo(int $timestamp, int $now): string
    {
        $seconds = max(0, $now - $timestamp);

        if ($seconds < 60) {
            return $seconds . 's ago';
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            return $minutes . 'm ago';
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            return $hours . 'h ago';
        }

        return intdiv($hours, 24) . 'd ago';
    }

    private function formatSlotLabel(int $slotStart, int $slotEnd, int $now): string
    {
        $startDayOffset = (int) floor(($slotStart - $now) / 86400);
        $endDayOffset = (int) floor(($slotEnd - $now) / 86400);

        $startTime = date('H:i', $slotStart);
        $endTime = date('H:i', $slotEnd);

        $startLabel = $this->formatDayOffset($startDayOffset) . ' ' . $startTime;
        $endLabel = $this->formatDayOffset($endDayOffset) . ' ' . $endTime;

        return $startLabel . ' → ' . $endLabel;
    }

    private function formatDayOffset(int $dayOffset): string
    {
        if ($dayOffset === 0) {
            return 'Aujourd’hui';
        }

        if ($dayOffset === -1) {
            return 'Hier';
        }

        return 'J' . $dayOffset;
    }
}