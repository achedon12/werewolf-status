<?php

declare(strict_types=1);

use App\Application\Service\FlashService;

$errorMessage = FlashService::getError();

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin — Connexion</title>
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
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center">
<main class="w-full max-w-md p-6">
    <div class="bg-slate-800 rounded-2xl p-6 shadow-lg">
        <div class="mb-6 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-xl overflow-hidden bg-slate-700 flex items-center justify-center">
                <img src="/logo.png" alt="logo" class="w-full h-full object-cover" onerror="this.style.display='none'">
            </div>

            <h1 class="text-2xl font-bold">Connexion admin</h1>
            <p class="text-sm text-slate-400 mt-1">loupsgarous.net — Statut</p>
        </div>

        <?php if ($errorMessage !== null): ?>
            <div class="mb-4 rounded-lg border border-red-500/40 bg-red-950/40 px-4 py-3 text-sm text-red-200">
                <?= e($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/admin/login" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-slate-300 mb-1">
                    Nom d’utilisateur
                </label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    autocomplete="username"
                    required
                    class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-slate-100 outline-none focus:border-emerald-400"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-300 mb-1">
                    Mot de passe
                </label>

                <div class="relative">
                    <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 pr-11 text-slate-100 outline-none focus:border-emerald-400"
                    >

                    <button
                            type="button"
                            onclick="togglePassword('password', this)"
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

            <button
                type="submit"
                class="w-full rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-900 hover:bg-emerald-300 transition"
            >
                Se connecter
            </button>
        </form>

        <div class="mt-5 text-center">
            <a href="/" class="text-sm text-slate-400 hover:text-emerald-300 transition">
                Retour à la page de statut
            </a>
        </div>
    </div>
</main>
</body>
</html>