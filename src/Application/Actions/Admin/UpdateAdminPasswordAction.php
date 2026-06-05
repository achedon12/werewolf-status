<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use App\Application\Service\FlashService;
use App\Infrastructure\Persistence\Admin\PdoAdminUserRepository;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use Psr\Http\Message\ResponseInterface as Response;

final class UpdateAdminPasswordAction extends Action
{
    protected function action(): Response
    {
        $adminId = (int) ($this->args['id'] ?? 0);
        $data = (array) $this->request->getParsedBody();

        $password = (string) ($data['password'] ?? '');

        if ($adminId <= 0) {
            FlashService::error('Admin invalide.');
            return $this->redirect('/admin');
        }

        if (strlen($password) < 8) {
            FlashService::error('Le mot de passe doit contenir au moins 8 caractères.');
            return $this->redirect('/admin');
        }

        $pdo = ConnectionFactory::create();
        $repository = new PdoAdminUserRepository($pdo);

        $repository->updatePassword($adminId, $password);

        FlashService::success('Mot de passe mis à jour avec succès.');
        return $this->redirect('/admin');
    }

    private function redirect(string $url): Response
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}