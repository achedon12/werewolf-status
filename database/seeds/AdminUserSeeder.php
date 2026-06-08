<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class AdminUserSeeder extends AbstractSeed
{
    public function run(): void
    {
        $username = $_ENV['ADMIN_USER_NAME'] ?? getenv('ADMIN_USER_NAME') ?: null;
        $password = $_ENV['ADMIN_USER_PASSWORD'] ?? getenv('ADMIN_USER_PASSWORD') ?: null;

        if ($username === null || trim($username) === '') {
            throw new RuntimeException('ADMIN_USER_NAME is missing in .env');
        }

        if ($password === null || trim($password) === '') {
            throw new RuntimeException('ADMIN_USER_PASSWORD is missing in .env');
        }

        if (strlen($password) < 8) {
            throw new RuntimeException('ADMIN_USER_PASSWORD must contain at least 8 characters');
        }

        $username = trim($username);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $escapedUsername = $this->escapeSql($username);
        $escapedPasswordHash = $this->escapeSql($passwordHash);

        $this->execute("
            INSERT INTO admin_users (
                username,
                password_hash,
                role,
                is_enabled,
                created_at,
                updated_at
            ) VALUES (
                '{$escapedUsername}',
                '{$escapedPasswordHash}',
                'admin',
                1,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                password_hash = VALUES(password_hash),
                role = VALUES(role),
                is_enabled = VALUES(is_enabled),
                updated_at = NOW()
        ");
    }

    private function escapeSql(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}