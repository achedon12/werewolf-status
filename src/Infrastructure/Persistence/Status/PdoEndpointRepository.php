<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Status;

use App\Domain\Status\Endpoint;
use App\Domain\Status\EndpointRepository;
use PDO;

final class PdoEndpointRepository implements EndpointRepository
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function findEnabled(): array
    {
        $stmt = $this->pdo->query(
            'SELECT *
             FROM endpoints
             WHERE is_enabled = 1
             ORDER BY id ASC'
        );

        return array_map(
            fn(array $row): Endpoint => Endpoint::fromArray($row),
            $stmt->fetchAll()
        );
    }

    public function findById(int $id): ?Endpoint
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM endpoints
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $id,
        ]);

        $endpoint = $stmt->fetch();

        return $endpoint ? Endpoint::fromArray($endpoint) : null;
    }

    public function create(
        string $name,
        string $checkUrl,
        ?string $publicUrl,
        string $uptimeUnit,
        bool $discordNotificationsEnabled,
        ?string $discordWebhookUrl
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO endpoints (
            name,
            check_url,
            public_url,
            uptime_unit,
            is_enabled,
            discord_notifications_enabled,
            discord_webhook_url,
            created_at,
            updated_at
        ) VALUES (
            :name,
            :check_url,
            :public_url,
            :uptime_unit,
            1,
            :discord_notifications_enabled,
            :discord_webhook_url,
            NOW(),
            NOW()
        )'
        );

        $stmt->execute([
            'name' => $name,
            'check_url' => $checkUrl,
            'public_url' => $publicUrl,
            'uptime_unit' => $uptimeUnit,
            'discord_notifications_enabled' => $discordNotificationsEnabled ? 1 : 0,
            'discord_webhook_url' => $discordWebhookUrl,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT *
                 FROM endpoints
                 ORDER BY id ASC'
        );

        $rows = $stmt->fetchAll();

        return array_map(
            fn(array $row) => \App\Domain\Status\Endpoint::fromArray($row),
            $rows
        );
    }

    public function update(
        int $id,
        string $name,
        string $checkUrl,
        ?string $publicUrl,
        string $uptimeUnit,
        bool $discordNotificationsEnabled,
        ?string $discordWebhookUrl
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE endpoints
         SET name = :name,
             check_url = :check_url,
             public_url = :public_url,
             uptime_unit = :uptime_unit,
             discord_notifications_enabled = :discord_notifications_enabled,
             discord_webhook_url = :discord_webhook_url,
             updated_at = NOW()
         WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'check_url' => $checkUrl,
            'public_url' => $publicUrl,
            'uptime_unit' => $uptimeUnit,
            'discord_notifications_enabled' => $discordNotificationsEnabled ? 1 : 0,
            'discord_webhook_url' => $discordWebhookUrl,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM endpoints
         WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
        ]);
    }

    public function toggle(int $id): void
    {
        $endpoint = $this->findById($id);

        if ($endpoint === null) {
            return;
        }

        $isEnabled = $endpoint->isEnabled() ? 0 : 1;

        $stmt = $this->pdo->prepare(
            'UPDATE endpoints
         SET is_enabled = :is_enabled,
             updated_at = NOW()
         WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'is_enabled' => $isEnabled,
        ]);
    }
}