<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Status;

use PDO;

final class PdoSettingsRepository
{
    public const ALLOWED_DISPLAY_PERIODS = [
        1,
        3,
        6,
        12,
        24,
        48,
        72,
        168,
        336,
        720,
    ];

    public function __construct(
        private PDO $pdo
    ) {
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT setting_value
             FROM settings
             WHERE setting_key = :setting_key
             LIMIT 1'
        );

        $stmt->execute([
            'setting_key' => $key,
        ]);

        $value = $stmt->fetchColumn();

        if ($value === false) {
            return $default;
        }

        return (string) $value;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (
                setting_key,
                setting_value,
                updated_at
            ) VALUES (
                :setting_key,
                :setting_value,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_at = NOW()'
        );

        $stmt->execute([
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
    }

    public function getDisplayPeriodHours(): int
    {
        return (int) $this->get('display_period_hours', '48');
    }

    public function setDisplayPeriodHours(int $hours): void
    {
        $allowedPeriods = self::ALLOWED_DISPLAY_PERIODS;

        if (!in_array($hours, $allowedPeriods, true)) {
            throw new \InvalidArgumentException('Invalid display period');
        }

        $this->set('display_period_hours', (string) $hours);
    }
}
