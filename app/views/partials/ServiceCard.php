<?php

declare(strict_types=1);

$service = $service ?? [];
$name = $name ?? 'N/A';
$online = isServiceOnline($service);
$percent = $service['history']['uptime_percent'] ?? 0;
$startedAt = getStartedAt($service);
$slots = $service['history']['slots'] ?? [];
$cardClass = getServiceCardClass($online);
?>

<article class="<?= e($cardClass) ?> rounded-xl p-4 mb-4 flex items-center justify-between">
    <div class="flex items-center">
        <?php if ($online): ?>
            <span class="inline-block <?= e(getPercentColorClass((float) $percent)) ?> font-bold px-3 py-1 rounded-full mr-4">
        <?= e($percent) ?>%
      </span>
        <?php else: ?>
            <span class="inline-block bg-red-500 text-white font-bold px-3 py-1 rounded-full mr-4">
        Offline
      </span>
        <?php endif; ?>

        <div>
            <?php renderServiceName((string) $name, $service,$online); ?>

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
        <div class="text-sm text-slate-400">48h ago</div>

        <div class="relative flex gap-1 items-center h-10">
            <?php renderSlots($slots, $online); ?>
        </div>

        <div class="text-sm text-slate-400">now</div>
    </div>
</article>
