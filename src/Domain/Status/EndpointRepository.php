<?php

declare(strict_types=1);

namespace App\Domain\Status;

interface EndpointRepository
{
    public function findEnabled(): array;

    public function findById(int $id): ?array;

    public function create(
        string $name,
        string $checkUrl,
        ?string $publicUrl,
        string $uptimeUnit
    ): int;
}