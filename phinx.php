<?php

declare(strict_types=1);

$dotenvPath = __DIR__ . '/.env';

if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

return [
    'paths' => [
        'migrations' => __DIR__ . '/database/migrations',
        'seeds' => __DIR__ . '/database/seeds',
    ],

    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => $_ENV['APP_ENV'] === 'prod' ? 'production' : 'development',

        'development' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'name' => $_ENV['DB_DATABASE'] ?? 'status_page',
            'user' => $_ENV['DB_USERNAME'] ?? 'root',
            'pass' => $_ENV['DB_PASSWORD'] ?? '',
            'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
            'charset' => 'utf8mb4',
        ],

        'production' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'name' => $_ENV['DB_DATABASE'] ?? 'status_page',
            'user' => $_ENV['DB_USERNAME'] ?? 'status_user',
            'pass' => $_ENV['DB_PASSWORD'] ?? '',
            'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
            'charset' => 'utf8mb4',
        ],
    ],

    'version_order' => 'creation',
];