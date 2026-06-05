<?php

declare(strict_types=1);

require_once __DIR__ . '/partials/StatusHelpers.php';

const REFRESH_INTERVAL_SECONDS = 30;

$results = $results ?? [];
$status = getGlobalStatusData($results);
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
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
            <?php require __DIR__ . '/partials/ServiceCard.php'; ?>
        <?php endforeach; ?>
    </div>

    <footer class="mt-6 text-slate-500">
        <div>Mis à jour: <span id="update-time"><?= date('Y-m-d H:i:s') ?></span></div>
        <div>Actualisation dans: <span id="refresh-countdown"><?= e(REFRESH_INTERVAL_SECONDS) ?>s</span></div>
    </footer>
</div>

<footer>
    <p class="text-center text-slate-500">
        &copy; <?= date("Y") ?> Loup-Garou Online. Tous droits réservés.
    </p>
    <p class="text-center text-slate-500 text-sm">
        <a target="_blank" href="https://www.linkedin.com/in/théo-van-de-walle" rel="noopener noreferrer" class="text-slate-500 hover:text-emerald-400 transition">Developed by Théo Van de Walle</a>
    </p>
</footer>

<script src="./assets/js/status.js"></script>
</body>
</html>
