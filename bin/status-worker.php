<?php

declare(strict_types=1);

use App\Application\Service\DiscordNotificationService;
use App\Application\Service\DowntimeService;
use App\Application\Service\StatusChecker;
use App\Application\Service\StatusPayloadBuilder;
use App\Infrastructure\Notification\DiscordWebhookNotifier;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoDowntimeRepository;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use App\Infrastructure\Persistence\Status\PdoSettingsRepository;
use App\Infrastructure\Persistence\Status\StatusSnapshotRepository;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$intervalSeconds = (int) ($_ENV['STATUS_CHECK_INTERVAL'] ?? 30);

echo "Status worker started. Interval: {$intervalSeconds}s\n";

while (true) {
    try {
        $pdo = ConnectionFactory::create();

        $endpointRepository = new PdoEndpointRepository($pdo);
        $settingsRepository = new PdoSettingsRepository($pdo);
        $downtimeRepository = new PdoDowntimeRepository($pdo);

        $checker = new StatusChecker();

        $discordNotifier = new DiscordWebhookNotifier();
        $discordNotificationService = new DiscordNotificationService($discordNotifier);

        $downtimeService = new DowntimeService(
            $downtimeRepository,
            $discordNotificationService
        );

        $builder = new StatusPayloadBuilder(
            $endpointRepository,
            $settingsRepository,
            $checker,
            $downtimeService
        );

        $snapshotRepository = new StatusSnapshotRepository(
            __DIR__ . '/../var/cache/status_snapshot.json'
        );

        $payload = $builder->build();

        $snapshotRepository->save($payload);

        echo '[' . date('Y-m-d H:i:s') . "] status cache updated\n";
    } catch (Throwable $exception) {
        error_log('Status worker error: ' . $exception->getMessage());
        echo '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $exception->getMessage() . "\n";
    }

    sleep($intervalSeconds);
}