<?php

declare(strict_types=1);

use App\Application\Service\FlashService;

$endpoints = $endpoints ?? [];
$displayPeriodHours = $displayPeriodHours ?? 48;

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function selectedPeriod(int $current, int $value): string
{
    return $current === $value ? 'selected' : '';
}

function formatPeriodLabel(int $hours): string
{
    return match ($hours) {
        1 => '1 heure',
        3 => '3 heures',
        6 => '6 heures',
        12 => '12 heures',
        24 => '24 heures',
        48 => '48 heures',
        72 => '72 heures',
        168 => '1 semaine',
        336 => '2 semaines',
        720 => '30 jours',
        default => $hours . ' heures',
    };
}

function selectedUnit(string $current, string $value): string
{
    return $current === $value ? 'selected' : '';
}

function checked(bool $value): string
{
    return $value ? 'checked' : '';
}

$successMessage = FlashService::getSuccess();
$errorMessage = FlashService::getError();
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin — Status</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);

        if (!input) {
            return;
        }

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        button.innerHTML = isPassword ? getEyeOffIcon() : getEyeIcon();
    }

    function getEyeIcon() {
        return `
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="12" cy="12" r="3"/>
      </svg>
    `;
    }

    function getEyeOffIcon() {
        return `
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 3l18 18" stroke-linecap="round"/>
        <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" stroke-linecap="round"/>
        <path d="M9.88 5.09A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a18.2 18.2 0 0 1-2.8 3.7" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M6.6 6.6C3.6 8.6 2 12 2 12s3.5 7 10 7a10.7 10.7 0 0 0 5.4-1.4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `;
    }
</script>
<body class="bg-slate-900 text-slate-100 min-h-screen">
<div class="max-w-7xl mx-auto px-4 py-6 sm:p-6">
    <header class="flex flex-col gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4 w-full">
            <div class="w-14 h-14 shrink-0 rounded-xl overflow-hidden bg-slate-800 flex items-center justify-center">
                <img src="/logo.png" alt="logo" class="w-full h-full object-cover" onerror="this.style.display='none'">
            </div>

            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold">Administration</h1>
                <p class="text-sm text-slate-400">Gestion des endpoints et de l’affichage</p>
            </div>
        </div>

        <div class="flex flex-col gap-2 w-full sm:w-auto sm:flex-row sm:items-center">
            <a
                    href="/"
                    class="w-full sm:w-auto rounded-lg bg-slate-800 px-4 py-2 text-center text-sm font-bold text-slate-300 hover:bg-slate-700 transition whitespace-nowrap"
            >
                Voir le status
            </a>

            <form method="post" action="/admin/logout" class="w-full sm:w-auto">
                <button
                        type="submit"
                        class="w-full sm:w-auto rounded-lg bg-red-500 px-4 py-2 text-sm font-bold text-white hover:bg-red-400 transition whitespace-nowrap"
                >
                    Déconnexion
                </button>
            </form>
        </div>
    </header>

    <?php if ($successMessage !== null): ?>
        <div class="mb-6 rounded-lg border border-emerald-500/40 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== null): ?>
        <div class="mb-6 rounded-lg border border-red-500/40 bg-red-950/40 px-4 py-3 text-sm text-red-200">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <section class="bg-slate-800 rounded-xl p-5 mb-6">
        <h2 class="text-xl font-bold mb-4">Paramètres</h2>

        <form method="post" action="/admin/settings" class="flex flex-col md:flex-row md:items-end gap-4">
            <div>
                <label for="display_period_hours" class="block text-sm text-slate-300 mb-1">
                    Durée d’affichage
                </label>

                <select
                    id="display_period_hours"
                    name="display_period_hours"
                    class="rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                >
                    <?php foreach (($allowedDisplayPeriods ?? [24, 48, 72, 168]) as $period): ?>
                        <option value="<?= e($period) ?>" <?= selectedPeriod((int) $displayPeriodHours, (int) $period) ?>>
                            <?= e(formatPeriodLabel((int) $period)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button
                type="submit"
                class="rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-900 hover:bg-emerald-300 transition"
            >
                Enregistrer
            </button>
        </form>
    </section>

    <section class="bg-slate-800 rounded-xl p-5 mb-6">
        <h2 class="text-xl font-bold mb-4">Ajouter un endpoint</h2>

        <form method="post" action="/admin/endpoints" class="grid grid-cols-1 lg:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm text-slate-300 mb-1">Nom</label>
                <input
                    name="name"
                    type="text"
                    required
                    placeholder="Loups Garous"
                    class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                >
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm text-slate-300 mb-1">URL de check</label>
                <input
                    name="check_url"
                    type="url"
                    required
                    placeholder="https://example.com/api/health"
                    class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                >
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm text-slate-300 mb-1">URL publique</label>
                <input
                    name="public_url"
                    type="url"
                    placeholder="https://example.com"
                    class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                >
            </div>

            <div>
                <label class="block text-sm text-slate-300 mb-1">Uptime</label>
                <select
                    name="uptime_unit"
                    class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                >
                    <option value="seconds">Secondes</option>
                    <option value="milliseconds">Millisecondes</option>
                </select>
            </div>

            <div class="lg:col-span-6 flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input
                        type="checkbox"
                        name="discord_notifications_enabled"
                        checked
                        class="rounded border-slate-600 bg-slate-900"
                    >
                    Notifications Discord
                </label>

                <button
                    type="submit"
                    class="rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-900 hover:bg-emerald-300 transition"
                >
                    Ajouter
                </button>
            </div>
        </form>
    </section>

    <section class="bg-slate-800 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Endpoints</h2>
            <span class="text-sm text-slate-400"><?= count($endpoints) ?> endpoint(s)</span>
        </div>

        <?php if ($endpoints === []): ?>
            <div class="rounded-lg bg-slate-900 border border-slate-700 p-4 text-slate-400">
                Aucun endpoint configuré.
            </div>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($endpoints as $endpoint): ?>
            <?php
            $id = $endpoint->getId();
            $isEnabled = $endpoint->isEnabled();
            $discordEnabled = $endpoint->isDiscordNotificationsEnabled();

            $endpointCardClass = $isEnabled
                    ? 'border-emerald-500/40 bg-emerald-950/20'
                    : 'border-red-500/40 bg-red-950/20';
            ?>

            <article class="rounded-xl border <?= e($endpointCardClass) ?> p-4">
                <form method="post" action="/admin/endpoints/<?= e($id) ?>/update" class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Nom</label>
                        <input
                            name="name"
                            type="text"
                            value="<?= e($endpoint->getName()) ?>"
                            required
                            class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                        >
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm text-slate-300 mb-1">URL de check</label>
                        <input
                            name="check_url"
                            type="url"
                            value="<?= e($endpoint->getCheckUrl()) ?>"
                            required
                            class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                        >
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm text-slate-300 mb-1">URL publique</label>
                        <input
                            name="public_url"
                            type="url"
                            value="<?= e($endpoint->getPublicUrl()) ?>"
                            class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                        >
                    </div>

                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Uptime</label>
                        <select
                            name="uptime_unit"
                            class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                        >
                            <option value="seconds" <?= selectedUnit($endpoint->getUptimeUnit(), 'seconds') ?>>Secondes</option>
                            <option value="milliseconds" <?= selectedUnit($endpoint->getUptimeUnit(), 'milliseconds') ?>>Millisecondes</option>
                        </select>
                    </div>

                    <div class="lg:col-span-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="rounded-full px-3 py-1 text-xs font-bold <?= $isEnabled ? 'bg-emerald-400 text-slate-900' : 'bg-red-500 text-white' ?>">
                                <?= $isEnabled ? 'Actif' : 'Désactivé' ?>
                            </span>

                            <label class="flex items-center gap-2 text-sm text-slate-300">
                                <input
                                    type="checkbox"
                                    name="discord_notifications_enabled"
                                    <?= checked($discordEnabled) ?>
                                    class="rounded border-slate-600 bg-slate-900"
                                >
                                Notifications Discord
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full md:w-auto">
                            <button
                                type="submit"
                                class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-900 hover:bg-emerald-300 transition w-full"
                            >
                                Enregistrer
                            </button>
                </form>

                <form method="post" action="/admin/endpoints/<?= e($id) ?>/toggle">
                    <button
                        type="submit"
                        class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-bold text-slate-100 hover:bg-slate-600 transition w-full"
                    >
                        <?= $isEnabled ? 'Désactiver' : 'Activer' ?>
                    </button>
                </form>

                <form method="post" action="/admin/endpoints/<?= e($id) ?>/delete" onsubmit="return confirm('Supprimer cet endpoint et ses downtimes ?');">
                    <button
                        type="submit"
                        class="rounded-lg bg-red-500 px-4 py-2 text-sm font-bold text-white hover:bg-red-400 transition w-full"
                    >
                        Supprimer
                    </button>
                </form>
        </div>
</div>
</article>
<?php endforeach; ?>
</div>
</section>
<section class="bg-slate-800 rounded-xl p-5 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Admins</h2>
        <span class="text-sm text-slate-400"><?= count($admins ?? []) ?> admin(s)</span>
    </div>

    <form method="post" action="/admin/admins" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <label class="block text-sm text-slate-300 mb-1">Nom d’utilisateur</label>
            <input
                    name="username"
                    type="text"
                    required
                    class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
            >
        </div>

        <div>
            <label class="block text-sm text-slate-300 mb-1">Mot de passe</label>

            <div class="relative">
                <input
                        id="new-admin-password"
                        name="password"
                        type="password"
                        required
                        minlength="8"
                        class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 pr-11 text-slate-100 outline-none focus:border-emerald-400"
                >

                <button
                        type="button"
                        onclick="togglePassword('new-admin-password', this)"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-emerald-300 transition"
                        aria-label="Afficher le mot de passe"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex items-end">
            <button
                    type="submit"
                    class="w-full rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-900 hover:bg-emerald-300 transition"
            >
                Ajouter
            </button>
        </div>
    </form>

    <div class="space-y-4">
        <?php foreach (($admins ?? []) as $admin): ?>
            <?php
            $adminId = (int) $admin['id'];
            $isCurrentAdmin = $adminId === (int) ($_SESSION['admin_user_id'] ?? 0);
            $isEnabled = (bool) $admin['is_enabled'];

            $adminCardClass = $isEnabled
                    ? 'border-emerald-500/40 bg-emerald-950/20'
                    : 'border-red-500/40 bg-red-950/20';
            ?>


            <article class="rounded-xl border <?= e($adminCardClass) ?> p-4">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_420px] lg:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-lg font-semibold break-words"><?= e($admin['username']) ?></span>

                            <?php if ($isCurrentAdmin): ?>
                                <span class="rounded-full bg-slate-700 px-2 py-1 text-xs text-slate-300">Vous</span>
                            <?php endif; ?>

                            <span class="rounded-full px-3 py-1 text-xs font-bold <?= $isEnabled ? 'bg-emerald-400 text-slate-900' : 'bg-red-500 text-white' ?>">
          <?= $isEnabled ? 'Actif' : 'Désactivé' ?>
        </span>
                        </div>

                        <div class="text-sm text-slate-400 mt-1 break-words">
                            <?= e($admin['role']) ?> · Créé le <?= e($admin['created_at']) ?>
                        </div>
                    </div>

                    <div class="w-full">
                        <form method="post" action="/admin/admins/<?= e($adminId) ?>/password" class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]">
                            <div class="relative">
                                <input
                                        id="admin-password-<?= e($adminId) ?>"
                                        name="password"
                                        type="password"
                                        placeholder="Nouveau mot de passe"
                                        minlength="8"
                                        required
                                        class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 pr-11 text-sm text-slate-100 outline-none focus:border-emerald-400"
                                >

                                <button
                                        type="button"
                                        onclick="togglePassword('admin-password-<?= e($adminId) ?>', this)"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-emerald-300 transition"
                                        aria-label="Afficher le mot de passe"
                                >
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>

                            <button
                                    type="submit"
                                    class="w-full sm:w-auto rounded-lg bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-900 hover:bg-emerald-300 transition whitespace-nowrap"
                            >
                                Modifier
                            </button>
                        </form>

                        <?php if (!$isCurrentAdmin): ?>
                            <div class="grid grid-cols-1 gap-2 mt-2 sm:grid-cols-2">
                                <form method="post" action="/admin/admins/<?= e($adminId) ?>/toggle">
                                    <button
                                            type="submit"
                                            class="w-full rounded-lg bg-slate-700 px-4 py-2 text-sm font-bold text-slate-100 hover:bg-slate-600 transition"
                                    >
                                        <?= $isEnabled ? 'Désactiver' : 'Activer' ?>
                                    </button>
                                </form>

                                <form method="post" action="/admin/admins/<?= e($adminId) ?>/delete" onsubmit="return confirm('Supprimer cet admin ?');">
                                    <button
                                            type="submit"
                                            class="w-full rounded-lg bg-red-500 px-4 py-2 text-sm font-bold text-white hover:bg-red-400 transition"
                                    >
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
</div>

<footer class="mb-10">
    <p class="text-center text-slate-500">
        &copy; <?= date("Y") ?> Loup-Garou Online. Tous droits réservés.
    </p>
    <p class="text-center text-slate-500 text-sm">
        <a target="_blank" href="https://www.linkedin.com/in/théo-van-de-walle" rel="noopener noreferrer" class="text-slate-500 hover:text-emerald-400 transition">Developed by Théo Van de Walle</a>
    </p>
</footer>
</body>
</html>