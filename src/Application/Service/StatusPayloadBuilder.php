<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Infrastructure\Persistence\Status\PdoDowntimeRepository;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use App\Infrastructure\Persistence\Status\PdoSettingsRepository;

final class StatusPayloadBuilder
{
    public function __construct(
        private PdoEndpointRepository $endpointRepository,
        private PdoSettingsRepository $settingsRepository,
        private StatusChecker $checker,
        private DowntimeService $downtimeService
    ) {}

    public function build(): array
    {
        $periodHours = $this->settingsRepository->getDisplayPeriodHours();

        $results = [];

        foreach ($this->endpointRepository->findEnabled() as $endpoint) {
            $result = $this->checker->check($endpoint->getCheckUrl());

            $result['id'] = $endpoint->getId();
            $result['public_url'] = $endpoint->getPublicUrl();
            $result['check_url'] = $endpoint->getCheckUrl();
            $result['uptime_unit'] = $endpoint->getUptimeUnit();

            $results[$endpoint->getName()] = $this->downtimeService->handleCheck(
                $endpoint,
                $result,
                $periodHours
            );
        }

        $infos = [
            'json' => null,
            'error' => null,
        ];

        $infoUrl = $_ENV['INFO_URL'] ?? '';

        if ($infoUrl !== '') {
            $infos = $this->checker->check($infoUrl);
        }

        return [
            'results' => $results,
            'infos' => $infos,
            'generated_at' => date('Y-m-d H:i:s'),
            'period_hours' => $periodHours,
        ];
    }
}