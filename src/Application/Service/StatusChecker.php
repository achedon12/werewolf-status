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

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
        ]);

        $body = curl_exec($ch);
        $errNo = curl_errno($ch);
        $errMsg = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $result['http_code'] = $httpCode ?: null;

        if ($body === false || $errNo !== 0) {
            $result['error'] = 'cURL error: ' . ($errMsg ?: 'unknown');
            return $result;
        }

        $result['body'] = $body;

        $decoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $result['json'] = $decoded;
        }

        return $result;
    }
}