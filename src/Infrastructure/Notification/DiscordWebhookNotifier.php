<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

final class DiscordWebhookNotifier
{
    public function send(string $webhookUrl, string $content, array $embeds = []): bool
    {
        if ($webhookUrl === '') {
            return false;
        }

        $payload = [
            'content' => $content,
        ];

        if ($embeds !== []) {
            $payload['embeds'] = $embeds;
        }

        $ch = curl_init($webhookUrl);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($body === false || $httpCode < 200 || $httpCode >= 300) {
            error_log('Discord webhook failed: HTTP ' . $httpCode . ' ' . $error);
            return false;
        }

        return true;
    }
}
