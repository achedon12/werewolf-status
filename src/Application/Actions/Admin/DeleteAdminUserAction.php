<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use App\Application\Service\FlashService;
use App\Infrastructure\Persistence\Admin\PdoAdminUserRepository;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use Psr\Http\Message\ResponseInterface as Response;

final class DeleteAdminUserAction extends Action
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
            FlashService::error('Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirect('/admin');
        }

        $pdo = ConnectionFactory::create();
        $repository = new PdoAdminUserRepository($pdo);

        if ($repository->countEnabledAdmins() <= 1) {
            FlashService::error('Vous ne pouvez pas supprimer le dernier administrateur actif.');
            return $this->redirect('/admin');
        }

        $repository->delete($adminId);

        FlashService::success('Administrateur supprimé.');
        return $this->redirect('/admin');
    }

    private function redirect(string $url): Response
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}