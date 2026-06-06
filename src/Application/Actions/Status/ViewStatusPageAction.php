<?php

declare(strict_types=1);

namespace App\Application\Actions\Status;

use App\Application\Actions\Action;
use App\Application\Service\DiscordNotificationService;
use App\Application\Service\DowntimeService;
use App\Application\Service\StatusChecker;
use App\Infrastructure\Notification\DiscordWebhookNotifier;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoDowntimeRepository;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use App\Infrastructure\Persistence\Status\PdoSettingsRepository;
use Psr\Http\Message\ResponseInterface as Response;

final class ViewStatusPageAction extends Action
{
    protected function action(): Response
    {
        $pdo = ConnectionFactory::create();

        $endpointRepository = new PdoEndpointRepository($pdo);
        $downtimeRepository = new PdoDowntimeRepository($pdo);
        $settingsRepository = new PdoSettingsRepository($pdo);
        $discordNotifier = new DiscordWebhookNotifier();
        $discordNotificationService = new DiscordNotificationService($discordNotifier);

        $checker = new StatusChecker();

        $downtimeService = new DowntimeService(
            $downtimeRepository,
            $discordNotificationService
        );

        $periodHours = $settingsRepository->getDisplayPeriodHours();
        $results = [];

        foreach ($endpointRepository->findEnabled() as $endpoint) {
            $result = $checker->check($endpoint->getCheckUrl());

            $result['id'] = $endpoint->getId();
            $result['public_url'] = $endpoint->getPublicUrl();
            $result['check_url'] = $endpoint->getCheckUrl();
            $result['uptime_unit'] = $endpoint->getUptimeUnit();

            $results[$endpoint->getName()] = $downtimeService->handleCheck(
                $endpoint,
                $result,
                $periodHours
            );
        }

        $infos = $checker->check('https://loupsgarous.net/api/infos');

        $viewFile = __DIR__ . '/../../../../app/views/status.php';

        if (!file_exists($viewFile)) {
            $this->response->getBody()->write('Vue status introuvable : ' . $viewFile);
            return $this->response->withStatus(500);
        }

        ob_start();
        require $viewFile;
        $html = ob_get_clean();

        $this->response->getBody()->write($html);

        return $this->response;
    }
}