<?php

namespace App\Domain\Admin;

interface PdoEndpointRepository
{
    public function create(
        string $name,
        string $checkUrl,
        ?string $publicUrl,
        string $uptimeUnit,
        bool $discordNotificationsEnabled
    ): int;

    public function update(
        int $id,
        string $name,
        string $checkUrl,
        ?string $publicUrl,
        string $uptimeUnit,
        bool $discordNotificationsEnabled
    ): void;

    public function delete(int $id): void;
}
