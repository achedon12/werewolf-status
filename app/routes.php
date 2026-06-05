<?php

declare(strict_types=1);

use App\Application\Actions\Status\GetStatusAction;
use App\Application\Service\DowntimeService;
use App\Application\Service\StatusChecker;
use App\Infrastructure\Persistence\Status\JsonDowntimeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    // Root page shows status for the two known endpoints of loupsgarous.net
    $app->get('/', function (Request $request, Response $response) {

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
            __DIR__ . '/../data/downtimes.json'
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

        $viewFile = __DIR__ . '/views/status.php';

        if (file_exists($viewFile)) {
            ob_start();
            include $viewFile;
            $html = ob_get_clean();
        } else {
            $html = '<pre>View not found: ' . htmlspecialchars($viewFile, ENT_QUOTES | ENT_SUBSTITUTE) . '</pre>';
        }

        $response->getBody()->write($html);

        return $response;
    });

    // API endpoint for status data (JSON)
    $app->get("/api/status", GetStatusAction::class);
//    $app->get('/api/status', function (Request $request, Response $response) {
//        $endpoints = [
//            'Loups Garous' => 'https://loupsgarous.net/api/health',
//        ];
//
//        $results = [];
//
//        foreach ($endpoints as $key => $url) {
//            $result = ['url' => $url, 'json' => null, 'error' => null];
//
//            if (!function_exists('curl_version')) {
//                $result['error'] = 'cURL extension is not available';
//            } else {
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, $url);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
//                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
//                $body = @curl_exec($ch);
//                $errNo = curl_errno($ch);
//                $errMsg = curl_error($ch);
//                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//                curl_close($ch);
//
//                $result['http_code'] = $httpCode ?: null;
//                if ($body === false || $errNo !== 0) {
//                    $result['error'] = 'cURL error: ' . ($errMsg ?: 'unknown');
//                } else {
//                    $result['body'] = $body;
//                }
//            }
//
//            if (isset($result['body']) && $result['body'] !== null) {
//                $decoded = json_decode($result['body'], true);
//                if (json_last_error() === JSON_ERROR_NONE) {
//                    $result['json'] = $decoded;
//                }
//            }
//
//            $results[$key] = $result;
//        }
//
//        // Fetch infos metadata
//        $infosUrl = 'https://loupsgarous.net/api/infos';
//        $infos = ['url' => $infosUrl, 'json' => null, 'error' => null];
//
//        if (!function_exists('curl_version')) {
//            $infos['error'] = 'cURL extension is not available';
//        } else {
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, $infosUrl);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
//            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
//            $body = @curl_exec($ch);
//            $errNo = curl_errno($ch);
//            $errMsg = curl_error($ch);
//            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//            curl_close($ch);
//
//            $infos['http_code'] = $httpCode ?: null;
//            if ($body === false || $errNo !== 0) {
//                $infos['error'] = 'cURL error: ' . ($errMsg ?: 'unknown');
//            } else {
//                $infos['body'] = $body;
//                $decoded = json_decode($body, true);
//                if (json_last_error() === JSON_ERROR_NONE) {
//                    $infos['json'] = $decoded;
//                }
//            }
//        }
//
//        $response->getBody()->write(json_encode(['results' => $results, 'infos' => $infos], JSON_UNESCAPED_UNICODE));
//        return $response->withHeader('Content-Type', 'application/json');
//    });
};
