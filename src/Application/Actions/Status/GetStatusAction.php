<?php

declare(strict_types=1);

namespace App\Application\Actions\Status;

use App\Application\Actions\Action;
use App\Application\Service\DowntimeService;
use App\Application\Service\StatusChecker;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoDowntimeRepository;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use Psr\Http\Message\ResponseInterface as Response;

final class GetStatusAction extends Action
{
    protected function action(): Response
    {
        $pdo = ConnectionFactory::create();

        $endpointRepository = new PdoEndpointRepository($pdo);
        $downtimeRepository = new PdoDowntimeRepository($pdo);

        $checker = new StatusChecker();
        $downtimeService = new DowntimeService($downtimeRepository);

        $results = [];

        foreach ($endpointRepository->findEnabled() as $endpoint) {
            $result = $checker->check($endpoint->getCheckUrl());

            $result['id'] = (int) $endpoint->getId();
            $result['public_url'] = $endpoint->getPublicUrl();
            $result['check_url'] = $endpoint->getCheckUrl();
            $result['uptime_unit'] = $endpoint->getUptimeUnit() ?? 'seconds';

            $results[$endpoint->getName()] = $downtimeService->handleCheck($endpoint, $result);
        }

        return $this->respondWithData([
            'results' => $results,
        ]);
    }
}