<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Status\DowntimeRepository;
use App\Domain\Status\Endpoint;

final class DowntimeService
{
    public function __construct(
        private DowntimeRepository $repository
    ) {}

    public function handleCheck(Endpoint $endpoint, array $result, int $periodHours = 48): array
    {
        $endpointId = $endpoint->getId();
        $httpCode = $result['http_code'] ?? null;

        $isDown = $this->isResultDown($result);

        if ($isDown) {
            $this->repository->startDowntime(
                $endpointId,
                $httpCode,
                $result['error'] ?? 'Service down'
            );
        } else {
            $this->repository->endDowntime($endpointId);
        }

        $result['history'] = $this->repository->getStats(
            $endpointId,
            $periodHours,
            24
        );

        return $result;
    }

    private function isResultDown(array $result): bool
    {
        $httpCode = $result['http_code'] ?? null;

        if ($httpCode === null) {
            return true;
        }

        if (!empty($result['error'])) {
            return true;
        }

        if ($httpCode === 502) {
            return true;
        }

        if ($httpCode >= 500) {
            return true;
        }

        return false;
    }
}