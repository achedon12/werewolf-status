<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class SettingsSeeder extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("
            INSERT INTO settings (
                setting_key,
                setting_value,
                updated_at
            ) VALUES
                ('display_period_hours', '48', NOW()),
                ('status_check_interval', '30', NOW())
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_at = NOW()
        ");
    }
}