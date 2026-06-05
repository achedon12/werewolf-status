<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Status\DowntimeRepository;

final class DowntimeService
{
    public function __construct(
        private DowntimeRepository $repository
    ) {}

    public function handleCheck(string $service, array $result): array
    {
        $httpCode = $result['http_code'] ?? null;
        $error = $result['error'] ?? null;
        $uptimeUnit = (int)($result['uptime_unit'] ?? 1);

        $write = isset($result['json']['uptime']) ? 'uptime' : (isset($result['json']['uptimeMs']) ? 'uptimeMs' : null);
        $hasUptime = isset($result['json'][$write]) && $result['json'][$write] !== '';

        $isDown = $httpCode < 200
            || $httpCode >= 300
            || !isset($result['json'])
            || !$hasUptime
            || $result['json']["status"] === 'down';

        if ($isDown) {
            $this->repository->startDowntime(
                $service,
                $httpCode,
                $error ?? 'HTTP 502'
            );
        } else {
            $this->repository->endDowntime($service);
        }

        if($hasUptime){
            $result['json']['uptime'] /= $uptimeUnit;
        }

        $result['history'] = $this->repository->getDailyStats($service);

        return $result;
    }
}