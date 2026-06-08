<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\Admin\PdoAdminUserRepository;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use App\Infrastructure\Persistence\Status\PdoSettingsRepository;
use Psr\Http\Message\ResponseInterface as Response;

final class ViewAdminAction extends Action
{
    protected function action(): Response
    {
        $pdo = ConnectionFactory::create();

        $endpointRepository = new PdoEndpointRepository($pdo);
        $settingsRepository = new PdoSettingsRepository($pdo);
        $adminRepository = new PdoAdminUserRepository($pdo);
        $admins = $adminRepository->findAll();

        $endpoints = $endpointRepository->findAll();
        $displayPeriodHours = $settingsRepository->getDisplayPeriodHours();
        $allowedDisplayPeriods = PdoSettingsRepository::ALLOWED_DISPLAY_PERIODS;

        $viewFile = __DIR__ . '/../../../../app/views/admin/index.php';

        if (!file_exists($viewFile)) {
            $this->response->getBody()->write('Vue admin introuvable : ' . $viewFile);
            return $this->response->withStatus(500);
        }

        ob_start();
        require $viewFile;
        $html = ob_get_clean();

        $this->response->getBody()->write($html);

        return $this->response;
    }
}
