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
        string $service,
        ?int $httpCode = null,
        ?string $reason = null
    ): void {
        $data = $this->read();

        if (!isset($data['services'][$service])) {
            $data['services'][$service] = $this->emptyService();
        }

        if ($data['services'][$service]['current_status'] === 'down') {
            return;
        }

        $now = date('c');

        $data['services'][$service]['current_status'] = 'down';
        $data['services'][$service]['current_down_started_at'] = $now;

        $data['services'][$service]['events'][] = [
            'service' => $service,
            'down_at' => $now,
            'up_at' => null,
            'http_code' => $httpCode,
            'reason' => $reason ?? ('HTTP ' . $httpCode),
        ];

        $this->write($data);
    }

    public function endDowntime(string $service): void
    {
        $data = $this->read();

        if (!isset($data['services'][$service])) {
            $data['services'][$service] = $this->emptyService();
        }

        if ($data['services'][$service]['current_status'] === 'up') {
            return;
        }

        $now = date('c');

        $data['services'][$service]['current_status'] = 'up';
        $data['services'][$service]['current_down_started_at'] = null;

        $lastIndex = count($data['services'][$service]['events']) - 1;

        if (
            $lastIndex >= 0
            && $data['services'][$service]['events'][$lastIndex]['up_at'] === null
        ) {
            $data['services'][$service]['events'][$lastIndex]['up_at'] = $now;
        }

        $this->write($data);
    }

    public function isCurrentlyDown(string $service): bool
    {
        $data = $this->read();

        return ($data['services'][$service]['current_status'] ?? 'up') === 'down';
    }

    public function getDailyStats(string $service): array
    {
        $data = $this->read();
        $events = $data['services'][$service]['events'] ?? [];

        $now = time();
        $periodStart = $now - (24 * 3600);

        $slots = [];
        $totalDowntimeSeconds = 0;

        for ($i = 0; $i < 24; $i++) {
            $slotStart = $periodStart + ($i * 3600);
            $slotEnd = $slotStart + 3600;

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

            $downtimeSecondsInSlot = min($downtimeSecondsInSlot, 3600);
            $totalDowntimeSeconds += $downtimeSecondsInSlot;

            if ($downtimeSecondsInSlot === 0) {
                $status = 'up';
            } elseif ($downtimeSecondsInSlot >= 3600) {
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

        $totalPeriodSeconds = 24 * 3600;
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
}