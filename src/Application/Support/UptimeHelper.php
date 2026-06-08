<?php

declare(strict_types=1);

namespace App\Application\Support;

final class UptimeHelper
{
    public static function toStartedTimestamp(int|string|null $uptime, string $unit = 'seconds'): ?int
    {
        if ($uptime === null) {
            return null;
        }

        if (is_int($uptime) || is_numeric($uptime)) {
            return self::numericToStartedTimestamp((float) $uptime, $unit);
        }

        return self::textToStartedTimestamp($uptime);
    }

    public static function formatDuration(int $startedAt): string
    {
        $seconds = max(0, time() - $startedAt);

        $days = intdiv($seconds, 86400);
        $seconds %= 86400;

        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;

        $minutes = intdiv($seconds, 60);
        $seconds %= 60;

        if ($days > 0) {
            return $days . 'j ' . $hours . 'h';
        }

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        if ($minutes > 0) {
            return $minutes . 'm ' . $seconds . 's';
        }

        return $seconds . 's';
    }

    private static function numericToStartedTimestamp(float $uptime, string $unit): ?int
    {
        if ($uptime <= 0) {
            return null;
        }

        $now = time();

        return match ($unit) {
            'milliseconds' => $now - (int) floor($uptime / 1000),

            'timestamp_seconds' => (int) $uptime <= $now
                ? (int) $uptime
                : null,

            'timestamp_milliseconds' => (int) floor($uptime / 1000) <= $now
                ? (int) floor($uptime / 1000)
                : null,

            default => $now - (int) $uptime,
        };
    }

    private static function textToStartedTimestamp(string $uptime): ?int
    {
        $text = trim($uptime);

        if ($text === '') {
            return null;
        }

        $timestamp = strtotime($text);

        if ($timestamp !== false && $timestamp <= time()) {
            return $timestamp;
        }

        if (preg_match('/^(\d+):(\d{2})(?::(\d{2}))?$/', $text, $matches)) {
            $seconds = ((int) $matches[1] * 3600)
                + ((int) $matches[2] * 60)
                + (int) ($matches[3] ?? 0);

            return time() - $seconds;
        }

        if (preg_match_all('/(\d+)\s*(d|days|h|hours|m|minutes|s|seconds)/i', $text, $parts, PREG_SET_ORDER)) {
            $seconds = 0;

            foreach ($parts as $part) {
                $value = (int) $part[1];
                $unit = strtolower($part[2]);

                $seconds += match (true) {
                    str_starts_with($unit, 'd') => $value * 86400,
                    str_starts_with($unit, 'h') => $value * 3600,
                    str_starts_with($unit, 'm') => $value * 60,
                    str_starts_with($unit, 's') => $value,
                    default => 0,
                };
            }

            return $seconds > 0 ? time() - $seconds : null;
        }

        return null;
    }
}
