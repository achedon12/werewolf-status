<?php

declare(strict_types=1);

namespace App\Domain\Status;

final class Downtime
{
    public function __construct(
        private string $service,
        private string $downAt,
        private ?string $upAt,
        private ?int $httpCode,
        private ?string $reason
    ) {}

    public function getService(): string
    {
        return $this->service;
    }

    public function getDownAt(): string
    {
        return $this->downAt;
    }

    public function getUpAt(): ?string
    {
        return $this->upAt;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function isOpen(): bool
    {
        return $this->upAt === null;
    }

    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'down_at' => $this->downAt,
            'up_at' => $this->upAt,
            'http_code' => $this->httpCode,
            'reason' => $this->reason,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['service'] ?? ''),
            (string) ($data['down_at'] ?? ''),
            $data['up_at'] ?? null,
            isset($data['http_code']) ? (int) $data['http_code'] : null,
            $data['reason'] ?? null
        );
    }
}