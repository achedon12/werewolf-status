<?php

declare(strict_types=1);

$service = $service ?? [];
$name = $name ?? 'N/A';
$online = isServiceOnline($service);
$percent = $service['history']['uptime_percent'] ?? 0;
$startedAt = getStartedAt($service);
$slots = $service['history']['slots'] ?? [];
$cardClass = getServiceCardClass($online);
$periodHours = (int) ($service['history']['period_hours'] ?? 48);
$periodLabel = match ($periodHours) {
    168 => '7j ago',
    336 => '14j ago',
    720 => '30j ago',
    default => $periodHours . 'h ago',
};
?>

<article class="<?= e($cardClass) ?> rounded-xl p-4 mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div class="flex items-start gap-4">
        <?php if ($online): ?>
            <span class="inline-block <?= e(getPercentColorClass((float) $percent)) ?> font-bold px-3 py-1 rounded-full">
        <?= e($percent) ?>%
      </span>
        <?php else: ?>
            <span class="inline-block bg-red-500 text-white font-bold px-3 py-1 rounded-full">
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

    <div class="w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
        <div class="min-w-max flex items-center gap-4">


        <div class="text-sm text-slate-400"><?= e($periodLabel) ?></div>

        <div class="relative flex gap-1 items-center h-10">
            <?php renderSlots($slots, $online); ?>
        </div>

        <div class="text-sm text-slate-400">now</div>
        </div>
    </div>
</article>
