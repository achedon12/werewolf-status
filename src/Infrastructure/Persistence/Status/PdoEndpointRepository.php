<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Status;

use App\Domain\Status\EndpointRepository;
use PDO;

final class PdoEndpointRepository implements EndpointRepository
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function findEnabled(): array
    {
        $stmt = $this->pdo->query(
            'SELECT *
             FROM endpoints
             WHERE is_enabled = 1
             ORDER BY id ASC'
        );

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
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

        return $endpoint ?: null;
    }

    public function create(
        string $name,
        string $checkUrl,
        ?string $publicUrl,
        string $uptimeUnit
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO endpoints (
                name,
                check_url,
                public_url,
                uptime_unit,
                is_enabled,
                created_at,
                updated_at
            ) VALUES (
                :name,
                :check_url,
                :public_url,
                :uptime_unit,
                1,
                NOW(),
                NOW()
            )'
        );

        $stmt->execute([
            'name' => $name,
            'check_url' => $checkUrl,
            'public_url' => $publicUrl,
            'uptime_unit' => $uptimeUnit,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}