<?php

declare(strict_types=1);

namespace App\Domain\Admin;

interface AdminUserRepository
{
    public function findAll(): array;

    public function findByUsername(string $username): ?array;

    public function create(string $username, string $password, string $role = 'admin'): int;

    public function updatePassword(int $id, string $password): void;

    public function toggle(int $id): void;

    public function delete(int $id): void;

    public function countEnabledAdmins(): int;
}
