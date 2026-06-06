<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Status;

final class StatusSnapshotRepository
{
    public function __construct(
        private string $filePath
    ) {}

    public function get(): ?array
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $content = file_get_contents($this->filePath);

        if ($content === false || $content === '') {
            return null;
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }

    public function save(array $payload): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $payload['cached_at'] = date('Y-m-d H:i:s');

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        if ($json === false) {
            throw new \RuntimeException('Impossible de générer le cache status.');
        }

        $temporaryFile = $this->filePath . '.tmp';

        file_put_contents($temporaryFile, $json, LOCK_EX);
        rename($temporaryFile, $this->filePath);
    }
}