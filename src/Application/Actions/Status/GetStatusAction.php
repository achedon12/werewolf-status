<?php

declare(strict_types=1);

namespace App\Application\Actions\Status;

use App\Application\Service\DowntimeService;
use App\Application\Service\StatusChecker;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoDowntimeRepository;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetStatusAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $pdo = ConnectionFactory::create();

        $endpointRepository = new PdoEndpointRepository($pdo);
        $downtimeRepository = new PdoDowntimeRepository($pdo);

        $checker = new StatusChecker();
        $downtimeService = new DowntimeService($downtimeRepository);

        $results = [];

        foreach ($endpointRepository->findEnabled() as $endpoint) {
            $result = $checker->check($endpoint['check_url']);

            $result['id'] = (int) $endpoint['id'];
            $result['public_url'] = $endpoint['public_url'];
            $result['check_url'] = $endpoint['check_url'];
            $result['uptime_unit'] = $endpoint['uptime_unit'];

            $results[$endpoint['name']] = $downtimeService->handleCheck($result, $result);
        }

        $payload = [
            'results' => $results,
        ];

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json');
    }
}