<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Status;

use App\Infrastructure\Persistence\Status\PdoSettingsRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoSettingsRepositoryTest extends TestCase
{
    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        $host = $_ENV['TEST_DB_HOST'] ?? getenv('TEST_DB_HOST') ?: null;
        $port = $_ENV['TEST_DB_PORT'] ?? getenv('TEST_DB_PORT') ?: 3306;
        $database = $_ENV['TEST_DB_DATABASE'] ?? getenv('TEST_DB_DATABASE') ?: null;
        $username = $_ENV['TEST_DB_USERNAME'] ?? getenv('TEST_DB_USERNAME') ?: null;
        $password = $_ENV['TEST_DB_PASSWORD'] ?? getenv('TEST_DB_PASSWORD') ?: '';

        if ($host === null || $database === null || $username === null) {
            self::markTestSkipped('Test database is not configured.');
        }

        $this->pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $this->pdo->exec('DROP TABLE IF EXISTS settings');

        $this->pdo->exec("
            CREATE TABLE settings (
                setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value VARCHAR(255) NOT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    protected function tearDown(): void
    {
        if ($this->pdo !== null) {
            $this->pdo->exec('DROP TABLE IF EXISTS settings');
        }
    }

    public function testGetDisplayPeriodHoursReturnsDefaultWhenMissing(): void
    {
        $repository = new PdoSettingsRepository($this->pdo);

        self::assertSame(48, $repository->getDisplayPeriodHours());
    }

    public function testSetDisplayPeriodHoursStoresValue(): void
    {
        $repository = new PdoSettingsRepository($this->pdo);

        $repository->setDisplayPeriodHours(72);

        self::assertSame(72, $repository->getDisplayPeriodHours());
    }
}
