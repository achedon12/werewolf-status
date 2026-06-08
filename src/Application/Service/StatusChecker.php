<?php

declare(strict_types=1);

namespace App\Application\Service;

final class StatusChecker
{
    public function check(string $url): array
    {
        $result = [
            'url' => $url,
            'http_code' => null,
            'json' => null,
            'body' => null,
            'error' => null,
        ];

        if (!function_exists('curl_version')) {
            $result['error'] = 'cURL extension is not available';
            return $result;
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $errorNumber = curl_errno($ch);
        $errorMessage = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $result['http_code'] = $httpCode ?: null;

        if ($body === false || $errorNumber !== 0) {
            $result['error'] = 'cURL error: ' . ($errorMessage ?: 'unknown');
            return $result;
        }

        $result['body'] = $body;

        $decoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $result['json'] = $decoded;
        }

        return $result;
    }
}
