<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Status\Endpoint;
use App\Infrastructure\Notification\DiscordWebhookNotifier;

final class DiscordNotificationService
{
    public function __construct(
        private DiscordWebhookNotifier $notifier
    ) {
    }

    public function notifyDown(Endpoint $endpoint, ?int $httpCode, ?string $reason): bool
    {
        if (!$this->canNotify($endpoint)) {
            return false;
        }

        $content = '🔴 **' . $endpoint->getName() . '** est hors ligne.';

        $embed = [
            'title' => 'Endpoint hors ligne',
            'color' => 15158332,
            'fields' => [
                [
                    'name' => 'Service',
                    'value' => $endpoint->getName(),
                    'inline' => true,
                ],
                [
                    'name' => 'HTTP',
                    'value' => $httpCode !== null ? (string) $httpCode : 'N/A',
                    'inline' => true,
                ],
                [
                    'name' => 'Raison',
                    'value' => $reason ?: 'Service down',
                    'inline' => false,
                ],
                [
                    'name' => 'URL',
                    'value' => $endpoint->getPublicUrl() ?: $endpoint->getCheckUrl(),
                    'inline' => false,
                ],
            ],
            'timestamp' => date('c'),
        ];

        return $this->notifier->send(
            (string) $endpoint->getDiscordWebhookUrl(),
            $content,
            [$embed]
        );
    }

    public function notifyUp(Endpoint $endpoint, string $downAt, string $upAt): bool
    {
        if (!$this->canNotify($endpoint)) {
            return false;
        }

        $duration = $this->formatDuration(
            strtotime($downAt) ?: time(),
            strtotime($upAt) ?: time()
        );

        $content = '🟢 **' . $endpoint->getName() . '** est revenu en ligne.';

        $embed = [
            'title' => 'Endpoint de nouveau en ligne',
            'color' => 3066993,
            'fields' => [
                [
                    'name' => 'Service',
                    'value' => $endpoint->getName(),
                    'inline' => true,
                ],
                [
                    'name' => 'Durée du downtime',
                    'value' => $duration,
                    'inline' => true,
                ],
                [
                    'name' => 'Début',
                    'value' => $downAt,
                    'inline' => false,
                ],
                [
                    'name' => 'Fin',
                    'value' => $upAt,
                    'inline' => false,
                ],
            ],
            'timestamp' => date('c'),
        ];

        return $this->notifier->send(
            (string) $endpoint->getDiscordWebhookUrl(),
            $content,
            [$embed]
        );
    }

    private function canNotify(Endpoint $endpoint): bool
    {
        return $endpoint->isDiscordNotificationsEnabled()
            && $endpoint->hasDiscordWebhook();
    }

    private function formatDuration(int $from, int $to): string
    {
        $seconds = max(0, $to - $from);

        $days = intdiv($seconds, 86400);
        $seconds %= 86400;

        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;

        $minutes = intdiv($seconds, 60);
        $seconds %= 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = $days . 'j';
        }

        if ($hours > 0 || $days > 0) {
            $parts[] = $hours . 'h';
        }

        if ($minutes > 0 || $hours > 0 || $days > 0) {
            $parts[] = $minutes . 'min';
        }

        if ($parts === []) {
            $parts[] = $seconds . 's';
        }

        return implode(' ', $parts);
    }
}
