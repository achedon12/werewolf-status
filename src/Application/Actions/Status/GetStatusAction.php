<?php

declare(strict_types=1);

namespace App\Application\Actions\Status;

use App\Application\Service\DowntimeService;
use App\Application\Service\StatusChecker;
use App\Infrastructure\Persistence\Status\JsonDowntimeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetStatusAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $endpoints = [
            'Jeu Loups Garous' => [
                'public_url' => 'https://loupsgarous.net/',
                'check_url' => 'https://loupsgarous.net/api/health',
                'uptime_unit' => 1
            ] ,
            'Status Loups Garous' => [
                'public_url' => 'https://status.loupsgarous.net',
                'check_url' => 'https://status.loupsgarous.net',
                'uptime_unit' => 1
            ],
            "Bot Discord Loups Garous" => [
                'public_url' => 'https://discord.gg/ybX6WFa4qx',
                'check_url' => 'https://loupsgarous.net/bot/health',
                'uptime_unit' => 1000
            ]
        ];

        $checker = new StatusChecker();

        $repository = new JsonDowntimeRepository(
            __DIR__ . '/../../../../data/downtimes.json'
        );

        $downtimeService = new DowntimeService($repository);

        $results = [];

        foreach ($endpoints as $name => $endpoint) {
            $result = $checker->check($endpoint['check_url']);

            $result['public_url'] = $endpoint['public_url'];
            $result['check_url'] = $endpoint['check_url'];
            $result['uptime_unit'] = $endpoint['uptime_unit'];

            $results[$name] = $downtimeService->handleCheck($name, $result);
        }

        $infos = $checker->check('https://loupsgarous.net/api/infos');

        $payload = [
            'results' => $results,
            'infos' => $infos,
        ];

        $response->getBody()->write(json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ));

        return $response->withHeader('Content-Type', 'application/json');
    }
}