<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Status;

use App\Domain\Status\DowntimeRepository;

final class JsonDowntimeRepository implements DowntimeRepository
{
    public function __construct(
        private string $filePath
    ) {}

    public function startDowntime(
        int $endpointId,
        ?int $httpCode = null,
        ?string $reason = null
    ): void {
        $data = $this->read();

        if (!isset($data['services'][$endpointId])) {
            $data['services'][$endpointId] = $this->emptyService();
        }

        if ($data['services'][$endpointId]['current_status'] === 'down') {
            return;
        }

        $now = date('c');

        $data['services'][$endpointId]['current_status'] = 'down';
        $data['services'][$endpointId]['current_down_started_at'] = $now;

        $data['services'][$endpointId]['events'][] = [
            'service' => $endpointId,
            'down_at' => $now,
            'up_at' => null,
            'http_code' => $httpCode,
            'reason' => $reason ?? ('HTTP ' . $httpCode),
        ];

        $this->write($data);
    }

    public function endDowntime(int $endpointId): void
    {
        $data = $this->read();

        if (!isset($data['services'][$endpointId])) {
            $data['services'][$endpointId] = $this->emptyService();
        }

        if ($data['services'][$endpointId]['current_status'] === 'up') {
            return;
        }

        $now = date('c');

        $data['services'][$endpointId]['current_status'] = 'up';
        $data['services'][$endpointId]['current_down_started_at'] = null;

        $lastIndex = count($data['services'][$endpointId]['events']) - 1;

        if (
            $lastIndex >= 0
            && $data['services'][$endpointId]['events'][$lastIndex]['up_at'] === null
        ) {
            $data['services'][$endpointId]['events'][$lastIndex]['up_at'] = $now;
        }

        $this->write($data);
    }

    public function isCurrentlyDown(int $endpointId): bool
    {
        $data = $this->read();

        return ($data['services'][$endpointId]['current_status'] ?? 'up') === 'down';
    }

    public function getDailyStats(string $service): array
    {
        $data = $this->read();
        $events = $data['services'][$service]['events'] ?? [];

        $now = time();
        $periodHours = 48;
        $slotCount = 24;
        $slotDuration = 2 * 3600;

        $periodStart = $now - ($periodHours * 3600);

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

                $isDowntimeStartInThisSlot = $downAt >= $slotStart && $downAt < $slotEnd;
                $isStillDown = $event['up_at'] === null;

                if ($isDowntimeStartInThisSlot && $isStillDown) {
                    $startedAgo = $this->formatAgo($downAt, $now);
                }
            }

            $downtimeSecondsInSlot = min($downtimeSecondsInSlot, $slotDuration);
            $totalDowntimeSeconds = 0;
            $totalDowntimeSeconds += $downtimeSecondsInSlot;

            if ($downtimeSecondsInSlot === 0) {
                $status = 'up';
            } elseif ($downtimeSecondsInSlot >= $slotDuration) {
                $status = 'down';
            } else {
                $status = 'partial';
            }

            $slots[] = [
                'index' => $i,
                'status' => $status,
                'downtime_seconds' => $downtimeSecondsInSlot,
                'uptime_seconds' => 3600 - $downtimeSecondsInSlot,
                'from' => date('Y-m-d H:i:s', $slotStart),
                'to' => date('Y-m-d H:i:s', $slotEnd),
                'label' => date('H:i', $slotStart) . ' - ' . date('H:i', $slotEnd),
                'started_ago' => $startedAgo,
            ];
        }

        $totalPeriodSeconds = $periodHours  * 3600;
        $uptimePercent = round((($totalPeriodSeconds - $totalDowntimeSeconds) / $totalPeriodSeconds) * 100, 2);

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
            'period_start' => date('Y-m-d H:i:s', $periodStart),
            'period_end' => date('Y-m-d H:i:s', $now),
            'period_hours' => $periodHours,
            'slot_duration_seconds' => $slotDuration,
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

        $days = intdiv($hours, 24);

        return $days . 'd ago';
    }

    private function emptyService(): array
    {
        return [
            'current_status' => 'up',
            'current_down_started_at' => null,
            'events' => [],
        ];
    }

    private function read(): array
    {
        if (!file_exists($this->filePath)) {
            return [
                'services' => [],
            ];
        }

        $content = file_get_contents($this->filePath);

        if ($content === false || trim($content) === '') {
            return [
                'services' => [],
            ];
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [
                'services' => [],
            ];
        }

        if (!isset($data['services'])) {
            $data['services'] = [];
        }

        return $data;
    }

    private function write(array $data): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $this->filePath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    public function getStats(int $endpointId, int $periodHours = 48, int $slotCount = 24): array
    {
        return [];
    }
}