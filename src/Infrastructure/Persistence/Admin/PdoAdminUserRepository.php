<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Admin;

use App\Domain\Admin\AdminUserRepository;
use PDO;

final class PdoAdminUserRepository implements AdminUserRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, username, role, is_enabled, created_at, updated_at
             FROM admin_users
             ORDER BY id ASC'
        );

        return $stmt->fetchAll();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM admin_users
             WHERE username = :username
             LIMIT 1'
        );

        $stmt->execute([
            'username' => $username,
        ]);

        $admin = $stmt->fetch();

        return $admin ?: null;
    }

    public function create(string $username, string $password, string $role = 'admin'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_users (
                username,
                password_hash,
                role,
                is_enabled,
                created_at,
                updated_at
            ) VALUES (
                :username,
                :password_hash,
                :role,
                1,
                NOW(),
                NOW()
            )'
        );

        $stmt->execute([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'role' => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updatePassword(int $id, string $password): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admin_users
             SET password_hash = :password_hash,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
        ]);
    }

    public function toggle(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admin_users
             SET is_enabled = CASE
                 WHEN is_enabled = 1 THEN 0
                 ELSE 1
             END,
             updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM admin_users
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
        ]);
    }

    public function countEnabledAdmins(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) FROM admin_users WHERE is_enabled = 1'
        );

        return (int) $stmt->fetchColumn();
    }
}
