<?php

declare(strict_types=1);

use App\Application\Actions\Status\GetStatusAction;
use App\Application\Service\DowntimeService;
use App\Application\Service\StatusChecker;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoDowntimeRepository;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {

    $buildStatusPayload = function (): array {
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
            $result['uptime_unit'] = $endpoint['uptime_unit'] ?? 'seconds';
            $results[$endpoint['name']] = $downtimeService->handleCheck($endpoint, $result);
        }

        return [
            'results' => $results,
            'infos' => [
                'json' => null,
                'error' => null,
            ],
        ];
    };


    $app->get('/', function (Request $request, Response $response) use ($buildStatusPayload): Response {
        $payload = $buildStatusPayload();

        $results = $payload['results'];
        $infos = $payload['infos'];

        $viewFile = __DIR__ . '/views/status.php';

        if (!file_exists($viewFile)) {
            $response->getBody()->write(
                '<pre>View not found: ' . htmlspecialchars($viewFile, ENT_QUOTES | ENT_SUBSTITUTE) . '</pre>'
            );

            return $response;
        }

        ob_start();
        require $viewFile;
        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    });

    $app->get('/api/status',  GetStatusAction::class);


};
