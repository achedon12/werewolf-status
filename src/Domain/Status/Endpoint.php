<?php

declare(strict_types=1);

namespace App\Domain\Status;

final class Endpoint
{
    public function __construct(
        private int $id,
        private string $name,
        private string $checkUrl,
        private ?string $publicUrl,
        private string $uptimeUnit,
        private bool $isEnabled,
        private bool $discordNotificationsEnabled
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCheckUrl(): string
    {
        return $this->checkUrl;
    }

    public function getPublicUrl(): ?string
    {
        return $this->publicUrl;
    }

    public function getUptimeUnit(): string
    {
        return $this->uptimeUnit;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function isDiscordNotificationsEnabled(): bool
    {
        return $this->discordNotificationsEnabled;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['check_url'],
            $data['public_url'] !== null ? (string) $data['public_url'] : null,
            (string) ($data['uptime_unit'] ?? 'seconds'),
            (bool) $data['is_enabled'],
            (bool) ($data['discord_notifications_enabled'] ?? true)
        );
    }
}