<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Status\DowntimeRepository;
use App\Domain\Status\Endpoint;

final class DowntimeService
{
    public function __construct(
        private readonly DowntimeRepository $repository,
        private readonly ?DiscordNotificationService $discordNotificationService = null
    ) {
    }

    public function handleCheck(
        Endpoint $endpoint,
        array $result,
        int $periodHours = 48
    ): array {
        $endpointId = $endpoint->getId();
        $httpCode = $result['http_code'] ?? null;

        $isDown = $this->isResultDown($result);

        if ($isDown) {
            $downtime = $this->repository->startDowntime(
                $endpointId,
                $httpCode,
                $result['error'] ?? 'Service down'
            );

            if ($downtime !== null) {
                $this->notifyDown($endpoint, $downtime, $httpCode, $result['error'] ?? null);
            }
        } else {
            $downtime = $this->repository->endDowntime($endpointId);

            if ($downtime !== null) {
                $this->notifyUp($endpoint, $downtime);
            }
        }

        $result['history'] = $this->repository->getStats(
            $endpointId,
            $periodHours,
            24
        );

        return $result;
    }

    private function isResultDown(array $result): bool
    {
        $httpCode = $result['http_code'] ?? null;

        if ($httpCode === null) {
            return true;
        }

        if (!empty($result['error'])) {
            return true;
        }

        return $httpCode >= 500;
    }

    private function notifyDown(
        Endpoint $endpoint,
        array $downtime,
        ?int $httpCode,
        ?string $reason
    ): void {

        if ($this->discordNotificationService === null) {
            return;
        }

        if (!empty($downtime['discord_down_notified_at'])) {
            return;
        }

        $sent = $this->discordNotificationService->notifyDown(
            $endpoint,
            $httpCode,
            $reason
        );

        if ($sent) {
            $this->repository->markDiscordDownNotified((int) $downtime['id']);
        }
    }

    private function notifyUp(Endpoint $endpoint, array $downtime): void
    {
        if ($this->discordNotificationService === null) {
            return;
        }

        if (empty($downtime['up_at'])) {
            return;
        }

        if (!empty($downtime['discord_up_notified_at'])) {
            return;
        }

        $sent = $this->discordNotificationService->notifyUp(
            $endpoint,
            (string) $downtime['down_at'],
            (string) $downtime['up_at']
        );

        if ($sent) {
            $this->repository->markDiscordUpNotified((int) $downtime['id']);
        }
    }
}
