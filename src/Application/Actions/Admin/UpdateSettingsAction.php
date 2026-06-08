<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use App\Application\Service\FlashService;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoSettingsRepository;
use Psr\Http\Message\ResponseInterface as Response;

final class UpdateSettingsAction extends Action
{
    protected function action(): Response
    {
        $data = (array) $this->request->getParsedBody();

        $periodHours = (int) ($data['display_period_hours'] ?? 48);

        if (!in_array($periodHours, PdoSettingsRepository::ALLOWED_DISPLAY_PERIODS, true)) {
            FlashService::error('Période d\'affichage invalide.');
            return $this->redirect('/admin');
        }

        $pdo = ConnectionFactory::create();
        $settingsRepository = new PdoSettingsRepository($pdo);

        $settingsRepository->setDisplayPeriodHours($periodHours);

        FlashService::success('Paramètres mis à jour.');
        return $this->redirect('/admin');
    }

    private function redirect(string $url): Response
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
