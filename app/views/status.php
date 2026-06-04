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
      <!-- logo: place your logo.png in public/ to have it shown -->
      <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-800 flex items-center justify-center">
        <img src="/logo.png" alt="logo" class="w-full h-full object-cover" onerror="this.style.display='none'" />
      </div>
      <div>
        <h1 class="text-3xl font-semibold">loupsgarous.net</h1>
        <?php if (isset($infos) && isset($infos['json']) && is_array($infos['json'])): ?>
          <div class="text-sm text-slate-400">Version: <?= htmlspecialchars($infos['json']['version'] ?? $infos['json']['ver'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE) ?><?php if (!empty($infos['json']['author'] ?? '')): ?> — <?= htmlspecialchars($infos['json']['author'], ENT_QUOTES | ENT_SUBSTITUTE) ?><?php endif; ?></div>
        <?php endif; ?>
      </div>
    </header>

    <?php
    // $results is expected to be provided by the caller
    $allOk = true;
    foreach ($results as $r) {
        $ok = (isset($r['http_code']) && $r['http_code'] >= 200 && $r['http_code'] < 300 && isset($r['json']));
        if (!$ok) { $allOk = false; break; }
    }
    ?>

    <div class="bg-slate-800 p-6 rounded-xl mb-6 flex items-center gap-4">
      <div class="w-12 h-12 rounded-full bg-emerald-400 flex items-center justify-center text-slate-900">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div>
        <?php if ($allOk): ?>
          <h2 class="text-2xl font-bold">Tous les systèmes sont opérationnels</h2>
        <?php else: ?>
          <h2 class="text-2xl font-bold">Certains services rencontrent des problèmes</h2>
        <?php endif; ?>
      </div>
    </div>

    <h3 class="text-xl text-slate-300 mb-4">Services</h3>

    <div id="services-container">
    <?php foreach ($results as $name => $r):
        $ok = (isset($r['http_code']) && $r['http_code'] >= 200 && $r['http_code'] < 300 && isset($r['json']));
        $percent = $ok ? 100 : 0;
    ?>
      <div class="bg-slate-800 rounded-xl p-4 mb-4 flex items-center justify-between">
        <div class="flex items-center">
          <span class="inline-block bg-emerald-400 text-slate-900 font-bold px-3 py-1 rounded-full mr-4"><?= htmlspecialchars((string)$percent) ?>%</span>
          <div>
            <div class="text-lg"><?= htmlspecialchars($name) ?></div>
            <?php
            // display uptime under the service name if available
            $uptime = null;
            if (isset($r['json']['uptime'])) {
                $uptime = $r['json']['uptime'];
            } elseif (isset($r['body'])) {
                if (preg_match('/uptime\s*[:=]\s*(.+)/i', $r['body'], $m)) {
                    $uptime = trim($m[1]);
                }
            }
            ?>
            <?php
            // format uptime as a started date when possible
            function uptime_to_started_date($uptime) {
                if ($uptime === null || $uptime === '') {
                    return null;
                }
                // numeric values
                if (is_numeric($uptime)) {
                    $n = (float)$uptime;
                    // if looks like unix timestamp (>= 1e9 -> ~2001+), treat as timestamp
                    if ($n >= 1000000000) {
                        return (int)$n;
                    }
                    // otherwise treat as seconds of uptime
                    return time() - (int)$n;
                }

                // try parse ISO/strtotime
                $ts = strtotime((string)$uptime);
                if ($ts !== false && $ts <= time()) {
                    return $ts;
                }

                // parse human duration like "12 days", "1 day 4:03:02", "4h30m"
                $s = (string)$uptime;
                // days
                if (preg_match('/(\d+)\s*days?/i', $s, $m)) {
                    $days = (int)$m[1];
                    return time() - ($days * 86400);
                }
                // hours/minutes/seconds like H:M:S
                if (preg_match('/^(\d+):(\d{2})(?::(\d{2}))?$/', $s, $m)) {
                    $h = (int)$m[1]; $mi = (int)$m[2]; $se = isset($m[3]) ? (int)$m[3] : 0;
                    $seconds = $h*3600 + $mi*60 + $se;
                    return time() - $seconds;
                }
                // hXmY formats
                if (preg_match_all('/(\d+)\s*(d|days|h|hours|m|minutes|s|seconds)/i', $s, $parts, PREG_SET_ORDER)) {
                    $seconds = 0;
                    foreach ($parts as $p) {
                        $val = (int)$p[1]; $unit = strtolower($p[2]);
                        if (strpos($unit,'d') === 0) $seconds += $val*86400;
                        elseif (strpos($unit,'h') === 0) $seconds += $val*3600;
                        elseif (strpos($unit,'m') === 0) $seconds += $val*60;
                        elseif (strpos($unit,'s') === 0) $seconds += $val;
                    }
                    if ($seconds > 0) return time() - $seconds;
                }

                return null;
            }

            $startedTs = uptime_to_started_date($uptime);
            if ($startedTs !== null) {
                // format date in localized way (French by default, but uses strftime-like formatting)
                $dt = (int)$startedTs;
                $now = time();
                $diff = $now - $dt;

                // build localized date string
                $dayNames = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
                $monthNames = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                $dayOfWeek = date('w', $dt);
                $day = date('d', $dt);
                $month = date('m', $dt);
                $year = date('Y', $dt);
                $time = date('H:i', $dt);

                $localizedDate = $day . ' ' . $monthNames[(int)$month] . ' ' . $year . ' ' . $time;

                // format duration
                $durationParts = [];
                $seconds = $diff;
                $days = intdiv($seconds, 86400);
                $seconds %= 86400;
                $hours = intdiv($seconds, 3600);
                $seconds %= 3600;
                $minutes = intdiv($seconds, 60);
                $seconds %= 60;

                if ($days > 0) { $durationParts[] = $days . 'd'; }
                if ($hours > 0 || $days > 0) { $durationParts[] = $hours . 'h'; }
                if ($minutes > 0 || $hours > 0 || $days > 0) { $durationParts[] = $minutes . 'm'; }
                if (count($durationParts) == 0) { $durationParts[] = $seconds . 's'; }

                $duration = implode(' ', $durationParts);

                echo '<div class="text-sm text-slate-400">Démarré le ' . htmlspecialchars($localizedDate, ENT_QUOTES | ENT_SUBSTITUTE). '</div>';
            } else {
                echo '<div class="text-sm text-slate-500">Uptime: —</div>';
            }
            ?>
          </div>
        </div>

        <div class="flex items-center gap-4">
        <div class="text-sm text-slate-400"><?php echo htmlspecialchars($duration, ENT_QUOTES | ENT_SUBSTITUTE); ?></div>
          <div class="flex gap-1">
            <?php for ($i=0;$i<24;$i++): ?>
              <div class="w-1.5 h-6 rounded <?= $ok ? 'bg-emerald-400' : 'bg-slate-700' ?>"></div>
            <?php endfor; ?>
          </div>
          <div class="text-sm text-slate-400">now</div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>

    <footer class="mt-6 text-slate-500">Mis à jour: <span id="update-time"><?= date('Y-m-d H:i:s') ?></span></footer>
  </div>

  <script>
    // Auto-refresh status every 30 seconds (1800000 ms)
    const REFRESH_INTERVAL = 30000;

    async function refreshStatus() {
      try {
        const response = await fetch('/api/status');
        if (!response.ok) throw new Error('API error');
        const data = await response.json();

        // Re-render services
        renderServices(data.results);
        
        // Update timestamp
        const now = new Date();
        const timeStr = now.getFullYear() + '-' + 
                        String(now.getMonth() + 1).padStart(2, '0') + '-' +
                        String(now.getDate()).padStart(2, '0') + ' ' +
                        String(now.getHours()).padStart(2, '0') + ':' +
                        String(now.getMinutes()).padStart(2, '0') + ':' +
                        String(now.getSeconds()).padStart(2, '0');
        document.getElementById('update-time').textContent = timeStr;
      } catch (error) {
        console.error('Refresh failed:', error);
      }
    }

    function renderServices(results) {
      const container = document.getElementById('services-container');
      let allOk = true;
      for (const [name, r] of Object.entries(results)) {
        const ok = r.http_code >= 200 && r.http_code < 300 && r.json;
        if (!ok) allOk = false;
      }

      let html = '';
      for (const [name, r] of Object.entries(results)) {
        const ok = r.http_code >= 200 && r.http_code < 300 && r.json;
        const percent = ok ? 100 : 0;
        
        // Calculate uptime display
        let uptimeDisplay = 'Uptime: —';
        if (r.json && r.json.uptime) {
          const uptimeText = formatUptimeAsDate(r.json.uptime);
          if (uptimeText) uptimeDisplay = uptimeText;
        }

        html += `
          <div class="bg-slate-800 rounded-xl p-4 mb-4 flex items-center justify-between">
            <div class="flex items-center">
              <span class="inline-block bg-emerald-400 text-slate-900 font-bold px-3 py-1 rounded-full mr-4">${percent}%</span>
              <div>
                <div class="text-lg">${escapeHtml(name)}</div>
                <div class="text-sm text-slate-400">${uptimeDisplay}</div>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="flex gap-1">
                ${Array(24).fill(0).map(() => `<div class="w-1.5 h-6 rounded ${ok ? 'bg-emerald-400' : 'bg-slate-700'}"></div>`).join('')}
              </div>
              <div class="text-sm text-slate-400">now</div>
            </div>
          </div>
        `;
      }
      container.innerHTML = html;
    }

    function formatUptimeAsDate(uptime) {
      if (!uptime) return null;

      let startedTs = null;
      
      // Check if numeric
      if (!isNaN(uptime)) {
        const n = parseFloat(uptime);
        if (n >= 1000000000) {
          // Unix timestamp
          startedTs = Math.floor(n * 1000);
        } else if (n > 0) {
          // Seconds of uptime
          startedTs = Date.now() - (n * 1000);
        }
      } else if (typeof uptime === 'string') {
        // Try to parse as date
        const ts = new Date(uptime).getTime();
        if (!isNaN(ts) && ts <= Date.now()) {
          startedTs = ts;
        }
      }

      if (startedTs) {
        const dt = new Date(startedTs);
        const days = Math.floor((Date.now() - startedTs) / 86400000);
        const hours = Math.floor(((Date.now() - startedTs) % 86400000) / 3600000);
        const mins = Math.floor(((Date.now() - startedTs) % 3600000) / 60000);

        let dur = '';
        if (days > 0) dur += days + 'd ';
        if (hours > 0 || days > 0) dur += hours + 'h ';
        if (mins > 0 || hours > 0 || days > 0) dur += mins + 'm';

        const monthNames = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        const day = String(dt.getDate()).padStart(2, '0');
        const month = monthNames[dt.getMonth()];
        const year = dt.getFullYear();
        const time = String(dt.getHours()).padStart(2, '0') + ':' + String(dt.getMinutes()).padStart(2, '0');

        return 'Démarré le ' + day + ' ' + month + ' ' + year + ' ' + time + ' · ' + dur.trim();
      }

      return null;
    }

    function escapeHtml(text) {
      const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
      return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Start auto-refresh
    setInterval(refreshStatus, REFRESH_INTERVAL);
  </script>
</body>
</html>

