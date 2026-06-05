<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use App\Application\Service\FlashService;
use App\Infrastructure\Persistence\Admin\PdoAdminUserRepository;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use Psr\Http\Message\ResponseInterface as Response;

final class ToggleAdminUserAction extends Action
{
    protected function action(): Response
    {
        $adminId = (int) ($this->args['id'] ?? 0);
        $currentAdminId = (int) ($_SESSION['admin_user_id'] ?? 0);

        if ($adminId <= 0) {
            FlashService::error('Admin invalide.');
            return $this->redirect('/admin');
        }

        if ($adminId === $currentAdminId) {
            FlashService::error('Vous ne pouvez pas désactiver votre propre compte.');
            return $this->redirect('/admin');
        }

        $pdo = ConnectionFactory::create();
        $repository = new PdoAdminUserRepository($pdo);

        $repository->toggle($adminId);

        FlashService::success('Admin activé ou désactivé.');

        return $this->redirect('/admin');
    }

    private function redirect(string $url): Response
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}