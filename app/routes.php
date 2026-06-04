<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    // Root page shows status for the two known endpoints of loupsgarous.net
    $app->get('/', function (Request $request, Response $response) {
        // Only check health endpoint for status. Infos endpoint is used for metadata display below.
        $endpoints = [
            'Loups Garous' => 'https://loupsgarous.net/api/health',
        ];

        // Prepare infos endpoint (metadata) separately
        $infosUrl = 'https://loupsgarous.net/api/infos';
        $infos = ['url' => $infosUrl, 'json' => null, 'error' => null];

        $results = [];

        foreach ($endpoints as $key => $url) {
            $result = ['url' => $url, 'json' => null, 'error' => null];

            // perform cURL request with SSL verification disabled (per user request)
            if (!function_exists('curl_version')) {
                $result['error'] = 'cURL extension is not available on this PHP installation.';
            } else {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                // NOTE: SSL verification disabled as requested; re-enable in production
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
                $body = @curl_exec($ch);
                $errNo = curl_errno($ch);
                $errMsg = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $result['http_code'] = $httpCode ?: null;
                $result['ssl_verif_disabled'] = true;
                if ($body === false || $errNo !== 0) {
                    $result['error'] = 'cURL error: ' . ($errMsg ?: 'unknown');
                } else {
                    $result['body'] = $body;
                }
            }

            if (isset($result['body']) && $result['body'] !== null) {
                $decoded = json_decode($result['body'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result['json'] = $decoded;
                }
            }

            $results[$key] = $result;
        }

        // Fetch infos metadata (separate from status checks)
        if (!function_exists('curl_version')) {
            $infos['error'] = 'cURL extension is not available on this PHP installation.';
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $infosUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            // SSL verification disabled per user request; re-enable for production
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            $body = @curl_exec($ch);
            $errNo = curl_errno($ch);
            $errMsg = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $infos['http_code'] = $httpCode ?: null;
            $infos['ssl_verif_disabled'] = true;
            if ($body === false || $errNo !== 0) {
                $infos['error'] = 'cURL error: ' . ($errMsg ?: 'unknown');
            } else {
                $infos['body'] = $body;
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $infos['json'] = $decoded;
                }
            }
        }

        // render template
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
    $app->get('/api/status', function (Request $request, Response $response) {
        $endpoints = [
            'health' => 'https://loupsgarous.net/api/health',
        ];

        $results = [];

        foreach ($endpoints as $key => $url) {
            $result = ['url' => $url, 'json' => null, 'error' => null];

            if (!function_exists('curl_version')) {
                $result['error'] = 'cURL extension is not available';
            } else {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
                $body = @curl_exec($ch);
                $errNo = curl_errno($ch);
                $errMsg = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $result['http_code'] = $httpCode ?: null;
                if ($body === false || $errNo !== 0) {
                    $result['error'] = 'cURL error: ' . ($errMsg ?: 'unknown');
                } else {
                    $result['body'] = $body;
                }
            }

            if (isset($result['body']) && $result['body'] !== null) {
                $decoded = json_decode($result['body'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result['json'] = $decoded;
                }
            }

            $results[$key] = $result;
        }

        // Fetch infos metadata
        $infosUrl = 'https://loupsgarous.net/api/infos';
        $infos = ['url' => $infosUrl, 'json' => null, 'error' => null];

        if (!function_exists('curl_version')) {
            $infos['error'] = 'cURL extension is not available';
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $infosUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            $body = @curl_exec($ch);
            $errNo = curl_errno($ch);
            $errMsg = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $infos['http_code'] = $httpCode ?: null;
            if ($body === false || $errNo !== 0) {
                $infos['error'] = 'cURL error: ' . ($errMsg ?: 'unknown');
            } else {
                $infos['body'] = $body;
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $infos['json'] = $decoded;
                }
            }
        }

        $response->getBody()->write(json_encode(['results' => $results, 'infos' => $infos], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
};
