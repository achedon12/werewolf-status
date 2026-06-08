<?php

declare(strict_types=1);

namespace App\Domain\Status;

final class Downtime
{
    public function __construct(
        private int $id,
        private int $endpointId,
        private string $downAt,
        private ?string $upAt,
        private ?int $httpCode,
        private ?string $reason
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEndpointId(): int
    {
        return $this->endpointId;
    }

    public function getDownAt(): string
    {
        return $this->downAt;
    }

    public function getUpAt(): ?string
    {
        return $this->upAt;
    }

    public function isOpen(): bool
    {
        return $this->upAt === null;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            (int) $data['endpoint_id'],
            (string) $data['down_at'],
            $data['up_at'] ?? null,
            isset($data['http_code']) ? (int) $data['http_code'] : null,
            $data['reason'] ?? null
        );
    }
}
