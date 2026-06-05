<?php

declare(strict_types=1);

const REFRESH_INTERVAL_SECONDS = 300;
const SLOT_COUNT = 24;

$results = $results ?? [];
$infos = $infos ?? [];

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

function uptimeToStartedTimestamp(mixed $uptime): ?int
{
    if ($uptime === null || $uptime === '') {
        return null;
    }

    if (is_numeric($uptime)) {
        $value = (float) $uptime;

        if ($value >= 1000000000) {
            return (int) $value;
        }

        return $value > 0 ? time() - (int) $value : null;
    }

    $text = (string) $uptime;
    $timestamp = strtotime($text);

    if ($timestamp !== false && $timestamp <= time()) {
        return $timestamp;
    }

    if (preg_match('/^(\d+):(\d{2})(?::(\d{2}))?$/', $text, $matches)) {
        $seconds = ((int) $matches[1] * 3600) + ((int) $matches[2] * 60) + (int) ($matches[3] ?? 0);
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

function isServiceOnline(array $service): bool
{
    return isset($service['http_code'])
        && $service['http_code'] >= 200
        && $service['http_code'] < 300
        && isset($service['json'])
        && uptimeToStartedTimestamp(getUptimeValue($service)) !== null;
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
        return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>';
    }

    return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 9v4" stroke-linecap="round"/>
        <path d="M12 17h.01" stroke-linecap="round"/>
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>';
}

function renderServiceName(string $name, array $service): void
{
    $publicUrl = $service['public_url'] ?? null;

    echo '<div class="text-lg flex items-center gap-2">';

    if ($publicUrl === null || $publicUrl === '') {
        echo '<span>' . e($name) . '</span></div>';
        return;
    }

    echo '<a href="' . e($publicUrl) . '" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-300 transition">'
        . e($name)
        . '</a>';

    echo '<a href="' . e($publicUrl) . '" target="_blank" rel="noopener noreferrer" class="text-slate-400 hover:text-emerald-300 transition translate-y-[2px]" title="Ouvrir le service">
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
        echo '<div class="relative h-8 flex items-center">
            <div class="w-1.5 h-6 rounded bg-slate-600" title="Hors ligne"></div>
        </div>';
    }
}

function renderEmptySlots(): void
{
    for ($i = 0; $i < SLOT_COUNT; $i++) {
        echo '<div class="relative h-8 flex items-center">
            <div class="w-1.5 h-6 rounded bg-slate-700"></div>
        </div>';
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
        $startedAgo = $slot['started_ago'] ?? '';

        echo '<div class="relative h-8 flex items-center">
            <div class="w-1.5 h-6 rounded ' . e($color) . '" title="' . e($title) . '"></div>';

        if ($startedAgo !== '') {
            echo '<div class="absolute top-7 left-1/2 -translate-x-1/2 text-[10px] text-slate-400 whitespace-nowrap">'
                . e($startedAgo)
                . '</div>';
        }

        echo '</div>';
    }
}

$status = getGlobalStatusData($results);
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>loupsgarous.net — Statut</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-900 text-slate-100 min-h-screen">
  <div class="max-w-6xl mx-auto p-6">
    <header class="flex items-center gap-4 mb-6">
      <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-800 flex items-center justify-center">
        <img src="/logo.png" alt="logo" class="w-full h-full object-cover" onerror="this.style.display='none'">
      </div>

      <div>
        <h1 class="text-3xl font-semibold">loupsgarous.net</h1>

        <?php if (isset($infos['json']) && is_array($infos['json'])): ?>
          <div class="text-sm text-slate-400">
            Version: <?= e($infos['json']['version'] ?? $infos['json']['ver'] ?? '') ?>
            <?php if (!empty($infos['json']['author'] ?? '')): ?>
              — <?= e($infos['json']['author']) ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </header>

    <section class="bg-slate-800 p-6 rounded-xl mb-6 flex items-center gap-4">
      <div id="global-status-icon" class="w-12 h-12 rounded-full <?= e($status['icon_class']) ?> flex items-center justify-center">
        <?= renderStatusIcon($status['icon']) ?>
      </div>

      <h2 id="global-status-title" class="text-2xl font-bold">
        <?= e($status['title']) ?>
      </h2>
    </section>

    <h3 class="text-xl text-slate-300 mb-4">Services</h3>

    <div id="services-container">
      <?php foreach ($results as $name => $service): ?>
        <?php
        $online = isServiceOnline($service);
        $percent = (float) ($service['history']['uptime_percent'] ?? 0);
        $startedAt = uptimeToStartedTimestamp(getUptimeValue($service));
        $slots = $service['history']['slots'] ?? [];
        ?>

        <article class="<?= e(getServiceCardClass($online)) ?> rounded-xl p-4 mb-4 flex items-center justify-between">
          <div class="flex items-center">
            <?php if ($online): ?>
              <span class="inline-block <?= e(getPercentColorClass($percent)) ?> font-bold px-3 py-1 rounded-full mr-4">
                <?= e($percent) ?>%
              </span>
            <?php else: ?>
              <span class="inline-block bg-red-500 text-white font-bold px-3 py-1 rounded-full mr-4">Offline</span>
            <?php endif; ?>

            <div>
              <?php renderServiceName((string) $name, $service); ?>

              <?php if ($online && $startedAt !== null): ?>
                <div class="text-sm text-slate-400">
                  Démarré le <?= e(formatFrenchDate($startedAt)) ?> · <?= e(formatDuration($startedAt)) ?>
                </div>
              <?php else: ?>
                <div class="text-sm text-red-300">Service hors ligne</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <div class="text-sm text-slate-400">24h ago</div>

            <div class="relative flex gap-1 items-center h-10">
              <?php renderSlots($slots, $online); ?>
            </div>

            <div class="text-sm text-slate-400">now</div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <footer class="mt-6 text-slate-500">
      <div>Mis à jour: <span id="update-time"><?= date('Y-m-d H:i:s') ?></span></div>
      <div>Actualisation dans: <span id="refresh-countdown"><?= e(REFRESH_INTERVAL_SECONDS) ?>s</span></div>
    </footer>
  </div>

  <script>
    const REFRESH_INTERVAL = <?= REFRESH_INTERVAL_SECONDS ?> * 1000;
    const SLOT_COUNT = <?= SLOT_COUNT ?>;
    const MONTH_NAMES = [
      'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
      'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ];

    let refreshCountdown = Math.floor(REFRESH_INTERVAL / 1000);

    function getUptimeValue(service) {
      if (service.json && service.json.uptime) {
        return service.json.uptime;
      }

      if (service.body) {
        const match = String(service.body).match(/uptime\s*[:=]\s*(.+)/i);
        return match ? match[1].trim() : null;
      }

      return null;
    }

    function uptimeToStartedTimestamp(uptime) {
      if (!uptime) {
        return null;
      }

      if (!isNaN(uptime)) {
        const value = parseFloat(uptime);

        if (value >= 1000000000) {
          return Math.floor(value * 1000);
        }

        return value > 0 ? Date.now() - (value * 1000) : null;
      }

      const parsedDate = new Date(uptime).getTime();

      if (!isNaN(parsedDate) && parsedDate <= Date.now()) {
        return parsedDate;
      }

      const timeParts = String(uptime).match(/^(\d+):(\d{2})(?::(\d{2}))?$/);

      if (timeParts) {
        const seconds = (Number(timeParts[1]) * 3600) + (Number(timeParts[2]) * 60) + Number(timeParts[3] || 0);
        return Date.now() - (seconds * 1000);
      }

      const durationMatches = [...String(uptime).matchAll(/(\d+)\s*(d|days|h|hours|m|minutes|s|seconds)/gi)];

      if (durationMatches.length === 0) {
        return null;
      }

      const seconds = durationMatches.reduce((total, match) => {
        const value = Number(match[1]);
        const unit = match[2].toLowerCase();

        if (unit.startsWith('d')) return total + value * 86400;
        if (unit.startsWith('h')) return total + value * 3600;
        if (unit.startsWith('m')) return total + value * 60;
        if (unit.startsWith('s')) return total + value;
        return total;
      }, 0);

      return seconds > 0 ? Date.now() - (seconds * 1000) : null;
    }

    function isServiceOnline(service) {
      return service.http_code >= 200
        && service.http_code < 300
        && Boolean(service.json)
        && uptimeToStartedTimestamp(getUptimeValue(service)) !== null;
    }

    function getGlobalStatusData(results) {
      const offlineCount = Object.values(results).filter(service => !isServiceOnline(service)).length;

      if (offlineCount === 0) {
        return {
          title: 'Tous les systèmes sont opérationnels',
          icon: 'check',
          iconClass: 'bg-emerald-400 text-slate-900',
        };
      }

      return {
        title: offlineCount === 1 ? '1 service est hors ligne' : `${offlineCount} services sont hors ligne`,
        icon: 'warning',
        iconClass: 'bg-red-500 text-white',
      };
    }

    function getStatusIcon(type) {
      if (type === 'check') {
        return `
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        `;
      }

      return `
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 9v4" stroke-linecap="round"></path>
          <path d="M12 17h.01" stroke-linecap="round"></path>
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
      `;
    }

    function updateGlobalStatus(results) {
      const status = getGlobalStatusData(results);
      const title = document.getElementById('global-status-title');
      const icon = document.getElementById('global-status-icon');

      if (!title || !icon) {
        return;
      }

      title.textContent = status.title;
      icon.className = `w-12 h-12 rounded-full ${status.iconClass} flex items-center justify-center`;
      icon.innerHTML = getStatusIcon(status.icon);
    }

    async function refreshStatus() {
      try {
        const response = await fetch('/api/status');

        if (!response.ok) {
          throw new Error('API error');
        }

        const data = await response.json();

        renderServices(data.results);
        updateGlobalStatus(data.results);
        updateUpdatedAt();

        refreshCountdown = Math.floor(REFRESH_INTERVAL / 1000);
        updateRefreshCountdown();
      } catch (error) {
        console.error('Refresh failed:', error);
      }
    }

    function renderServices(results) {
      const container = document.getElementById('services-container');

      if (!container) {
        return;
      }

      container.innerHTML = Object.entries(results).map(([name, service]) => renderServiceCard(name, service)).join('');
    }

    function renderServiceCard(name, service) {
      const online = isServiceOnline(service);
      const percent = service.history ? service.history.uptime_percent : 0;
      const badge = online ? renderPercentBadge(percent) : renderOfflineBadge();
      const uptime = online ? getUptimeDisplay(service) : 'Service hors ligne';
      const uptimeClass = online ? 'text-slate-400' : 'text-red-300';

      return `
        <article class="${getServiceCardClass(online)} rounded-xl p-4 mb-4 flex items-center justify-between">
          <div class="flex items-center">
            ${badge}

            <div>
              ${renderServiceName(name, service)}
              <div class="text-sm ${uptimeClass}">${escapeHtml(uptime)}</div>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <div class="text-sm text-slate-400">24h ago</div>
            <div class="relative flex gap-1 items-center h-10">${renderSlots(service, online)}</div>
            <div class="text-sm text-slate-400">now</div>
          </div>
        </article>
      `;
    }

    function renderPercentBadge(percent) {
      return `<span class="inline-block ${getPercentColorClass(percent)} font-bold px-3 py-1 rounded-full mr-4">${escapeHtml(percent)}%</span>`;
    }

    function renderOfflineBadge() {
      return '<span class="inline-block bg-red-500 text-white font-bold px-3 py-1 rounded-full mr-4">Offline</span>';
    }

    function renderServiceName(name, service) {
      const publicUrl = service.public_url;

      if (!publicUrl) {
        return `<div class="text-lg">${escapeHtml(name)}</div>`;
      }

      return `
        <div class="text-lg flex items-center gap-2">
          <a href="${escapeHtml(publicUrl)}" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-300 transition">
            ${escapeHtml(name)}
          </a>

          <a href="${escapeHtml(publicUrl)}" target="_blank" rel="noopener noreferrer" class="text-slate-400 hover:text-emerald-300 transition translate-y-[2px]" title="Ouvrir le service">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11.5 4.43" stroke-linecap="round" stroke-linejoin="round"></path>
              <path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 0 0 7.07 7.07l1.33-1.33" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          </a>
        </div>
      `;
    }

    function renderSlots(service, online) {
      if (!online) {
        return renderFixedSlots('bg-slate-600', 'Hors ligne');
      }

      if (!service.history || !service.history.slots) {
        return renderFixedSlots('bg-slate-700', '');
      }

      return service.history.slots.map(renderSlot).join('');
    }

    function renderFixedSlots(color, title) {
      return Array(SLOT_COUNT).fill(null).map(() => `
        <div class="relative h-8 flex items-center">
          <div class="w-1.5 h-6 rounded ${color}" title="${escapeHtml(title)}"></div>
        </div>
      `).join('');
    }

    function renderSlot(slot) {
      const status = slot.status || 'up';
      const color = getSlotColorClass(status);
      const statusText = getSlotStatusText(status);
      const label = slot.label || '';
      const startedAgo = slot.started_ago || '';

      return `
        <div class="relative h-8 flex items-center">
          <div class="w-1.5 h-6 rounded ${color}" title="${escapeHtml(label)} - ${escapeHtml(statusText)}"></div>
          ${startedAgo ? `<div class="absolute top-7 left-1/2 -translate-x-1/2 text-[10px] text-slate-400 whitespace-nowrap">${escapeHtml(startedAgo)}</div>` : ''}
        </div>
      `;
    }

    function getPercentColorClass(percent) {
      if (percent >= 95) return 'bg-emerald-400 text-slate-900';
      if (percent >= 80) return 'bg-yellow-400 text-slate-900';
      if (percent >= 50) return 'bg-orange-400 text-slate-900';
      return 'bg-red-500 text-white';
    }

    function getServiceCardClass(online) {
      return online ? 'bg-slate-800' : 'bg-red-950/40 border border-red-500/40';
    }

    function getSlotColorClass(status) {
      if (status === 'down') return 'bg-red-500';
      if (status === 'partial') return 'bg-orange-400';
      return 'bg-emerald-400';
    }

    function getSlotStatusText(status) {
      if (status === 'down') return 'Hors ligne pendant tout le créneau';
      if (status === 'partial') return 'Instable pendant ce créneau';
      return 'En ligne';
    }

    function getUptimeDisplay(service) {
      const startedAt = uptimeToStartedTimestamp(getUptimeValue(service));

      if (!startedAt) {
        return 'Uptime: —';
      }

      return `Démarré le ${formatFrenchDate(startedAt)} · ${formatDuration(startedAt)}`;
    }

    function formatFrenchDate(timestamp) {
      const date = new Date(timestamp);
      const day = String(date.getDate()).padStart(2, '0');
      const month = MONTH_NAMES[date.getMonth()];
      const year = date.getFullYear();
      const time = String(date.getHours()).padStart(2, '0') + ':' + String(date.getMinutes()).padStart(2, '0');

      return `${day} ${month} ${year} ${time}`;
    }

    function formatDuration(startedAt) {
      let seconds = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
      const days = Math.floor(seconds / 86400);
      seconds %= 86400;
      const hours = Math.floor(seconds / 3600);
      seconds %= 3600;
      const minutes = Math.floor(seconds / 60);
      seconds %= 60;

      const parts = [];

      if (days > 0) parts.push(`${days}d`);
      if (hours > 0 || days > 0) parts.push(`${hours}h`);
      if (minutes > 0 || hours > 0 || days > 0) parts.push(`${minutes}m`);

      return parts.length > 0 ? parts.join(' ') : `${seconds}s`;
    }

    function updateUpdatedAt() {
      const element = document.getElementById('update-time');

      if (!element) {
        return;
      }

      const now = new Date();
      element.textContent =
        now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0') + ' ' +
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0') + ':' +
        String(now.getSeconds()).padStart(2, '0');
    }

    function updateRefreshCountdown() {
      const countdownElement = document.getElementById('refresh-countdown');

      if (!countdownElement) {
        return;
      }

      countdownElement.textContent = formatCountdown(refreshCountdown);
      refreshCountdown--;

      if (refreshCountdown < 0) {
        refreshCountdown = Math.floor(REFRESH_INTERVAL / 1000);
      }
    }

    function formatCountdown(seconds) {
      const minutes = Math.floor(seconds / 60);
      const remainingSeconds = seconds % 60;

      if (minutes > 0 && remainingSeconds > 0) return `${minutes}min ${remainingSeconds}s`;
      if (minutes > 0) return `${minutes}min`;
      return `${remainingSeconds}s`;
    }

    function escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
      };

      return String(text).replace(/[&<>"']/g, character => map[character]);
    }

    updateRefreshCountdown();
    setInterval(updateRefreshCountdown, 1000);
    setInterval(refreshStatus, REFRESH_INTERVAL);

    var _paq = window._paq = window._paq || [];
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);

    (function() {
      var u = '//matomo.leoderoin.fr/';
      _paq.push(['setTrackerUrl', u + 'matomo.php']);
      _paq.push(['setSiteId', '5']);
      var d = document;
      var g = d.createElement('script');
      var s = d.getElementsByTagName('script')[0];
      g.async = true;
      g.src = u + 'matomo.js';
      s.parentNode.insertBefore(g, s);
    })();
  </script>
</body>
</html>
