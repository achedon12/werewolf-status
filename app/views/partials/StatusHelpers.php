<?php

declare(strict_types=1);

const SLOT_COUNT = 24;
const PERIOD_HOURS = 48;
const SLOT_DURATION_HOURS = 2;

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function getUptimeValue(array $service): mixed
{
    if (isset($service['json']['uptime'])) {
        return $service['json']['uptime'];
    }

    if (isset($service['body']) && preg_match('/uptime\s*[:=]\s*(.+)/i', (string) $service['body'], $matches)) {
        return trim($matches[1]);
    }

    return null;
}

function getUptimeUnit(array $service): string
{
    return (string) ($service['uptime_unit'] ?? 'seconds');
}

function uptimeToStartedTimestamp(mixed $uptime, string $unit = 'seconds'): ?int
{
    if ($uptime === null || $uptime === '') {
        return null;
    }

    if (is_numeric($uptime)) {
        $value = (float) $uptime;

        if ($value <= 0) {
            return null;
        }

        return match ($unit) {
            'milliseconds' => time() - (int) floor($value / 1000),
            'timestamp_seconds' => (int) $value,
            'timestamp_milliseconds' => (int) floor($value / 1000),
            default => time() - (int) $value,
        };
    }

    $text = (string) $uptime;
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

function getStartedAt(array $service): ?int
{
    return uptimeToStartedTimestamp(getUptimeValue($service), getUptimeUnit($service));
}

function isServiceOnline(array $service): bool
{
    return isset($service['http_code'])
        && $service['http_code'] >= 200
        && $service['http_code'] < 300
        && isset($service['json'])
        && getStartedAt($service) !== null;
}

function getGlobalStatusData(array $results): array
{
    $offlineCount = 0;

    foreach ($results as $service) {
        if (!isServiceOnline($service)) {
            $offlineCount++;
        }
    }

    if ($offlineCount === 0) {
        return [
            'title' => 'Tous les systèmes sont opérationnels',
            'icon' => 'check',
            'icon_class' => 'bg-emerald-400 text-slate-900',
        ];
    }

    return [
        'title' => $offlineCount === 1
            ? '1 service est hors ligne'
            : $offlineCount . ' services sont hors ligne',
        'icon' => 'warning',
        'icon_class' => 'bg-red-500 text-white',
    ];
}

function getPercentColorClass(float|int $percent): string
{
    return match (true) {
        $percent >= 95 => 'bg-emerald-400 text-slate-900',
        $percent >= 80 => 'bg-yellow-400 text-slate-900',
        $percent >= 50 => 'bg-orange-400 text-slate-900',
        default => 'bg-red-500 text-white',
    };
}

function getServiceCardClass(bool $online): string
{
    return $online
        ? 'bg-slate-800'
        : 'bg-red-950/40 border border-red-500/40';
}

function getSlotColorClass(string $status): string
{
    return match ($status) {
        'down' => 'bg-red-500',
        'partial' => 'bg-orange-400',
        default => 'bg-emerald-400',
    };
}

function getSlotStatusText(string $status): string
{
    return match ($status) {
        'down' => 'Hors ligne pendant tout le créneau',
        'partial' => 'Instable pendant ce créneau',
        default => 'En ligne',
    };
}

function formatFrenchDate(int $timestamp): string
{
    $months = [
        '',
        'janvier',
        'février',
        'mars',
        'avril',
        'mai',
        'juin',
        'juillet',
        'août',
        'septembre',
        'octobre',
        'novembre',
        'décembre',
    ];

    return date('d', $timestamp) . ' ' . $months[(int) date('m', $timestamp)] . ' ' . date('Y H:i', $timestamp);
}

function formatDuration(int $fromTimestamp): string
{
    $seconds = max(0, time() - $fromTimestamp);

    $days = intdiv($seconds, 86400);
    $seconds %= 86400;

    $hours = intdiv($seconds, 3600);
    $seconds %= 3600;

    $minutes = intdiv($seconds, 60);
    $seconds %= 60;

    $parts = [];

    if ($days > 0) {
        $parts[] = $days . 'd';
    }

    if ($hours > 0 || $days > 0) {
        $parts[] = $hours . 'h';
    }

    if ($minutes > 0 || $hours > 0 || $days > 0) {
        $parts[] = $minutes . 'm';
    }

    return $parts === [] ? $seconds . 's' : implode(' ', $parts);
}

function renderStatusIcon(string $type): string
{
    if ($type === 'check') {
        return '<svg class="w-6 h-6 translate-y-[2px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    return '<svg class="w-6 h-6 translate-y-[-2px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4" stroke-linecap="round"/><path d="M12 17h.01" stroke-linecap="round"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function renderServiceName(string $name, array $service, bool $online): void
{
    $publicUrl = $service['public_url'] ?? null;

    $textColor = $online ? "" : "text-slate-400";
    $hoverTextColor = $online ? "text-emerald-300" : "text-red-300";

    echo '<div class="text-lg flex items-center gap-2">';

    if ($publicUrl === null || $publicUrl === '') {
        echo "<span class={$textColor}>" . e($name) . '</span></div>';
        return;
    }

    echo '<a href="' . e($publicUrl) . '" target="_blank" rel="noopener noreferrer" class="hover:' . $hoverTextColor . ' transition ' . $textColor . '">'
        . e($name)
        . '</a>';

    echo '<a href="' . e($publicUrl) . '" target="_blank" rel="noopener noreferrer" class="hover:'.$hoverTextColor.' transition translate-y-[2px]" title="Ouvrir le service">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11.5 4.43" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 0 0 7.07 7.07l1.33-1.33" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>';

    echo '</div>';
}

function renderOfflineSlots(): void
{
    for ($i = 0; $i < SLOT_COUNT; $i++) {
        echo '<div class="relative h-8 flex items-center"><div class="w-1.5 h-6 rounded bg-slate-600" title="Hors ligne"></div></div>';
    }
}

function renderEmptySlots(): void
{
    for ($i = 0; $i < SLOT_COUNT; $i++) {
        echo '<div class="relative h-8 flex items-center"><div class="w-1.5 h-6 rounded bg-slate-700"></div></div>';
    }
}

function renderSlots(array $slots, bool $online): void
{
    if (!$online) {
        renderOfflineSlots();
        return;
    }

    if ($slots === []) {
        renderEmptySlots();
        return;
    }

    foreach ($slots as $slot) {
        $status = (string) ($slot['status'] ?? 'up');
        $color = getSlotColorClass($status);
        $title = ($slot['label'] ?? '') . ' - ' . getSlotStatusText($status);
        $startedAgo = (string) ($slot['started_ago'] ?? '');

        echo '<div class="relative h-8 flex items-center">';
        echo '<div class="w-1.5 h-6 rounded ' . e($color) . '" title="' . e($title) . '"></div>';

        if ($startedAgo !== '') {
            echo '<div class="absolute top-7 left-1/2 -translate-x-1/2 text-[10px] text-slate-400 whitespace-nowrap">'
                . e($startedAgo)
                . '</div>';
        }

        echo '</div>';
    }
}
