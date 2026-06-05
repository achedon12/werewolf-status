<?php

namespace App\Domain\Admin;

interface PdoSettingsRepository
{
    public function set(string $key, string $value): void;
}