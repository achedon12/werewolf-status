<?php

declare(strict_types=1);

namespace App\Domain\Status;

interface EndpointRepository
{
    public function findAll(): array;

    public function findEnabled(): array;

    public function findById(int $id): ?Endpoint;

    public function create(
        string $name,
        string $checkUrl,
        ?string $publicUrl,
        string $uptimeUnit,
        bool $discordNotificationsEnabled,
        ?string $discordWebhookUrl
    ): int;

    public function update(
        int $id,
        string $name,
        string $checkUrl,
        ?string $publicUrl,
        string $uptimeUnit,
        bool $discordNotificationsEnabled,
        ?string $discordWebhookUrl
    ): void;

    public function delete(int $id): void;

    public function toggle(int $id): void;
}
