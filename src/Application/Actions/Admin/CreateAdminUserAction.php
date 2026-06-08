<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use App\Application\Service\FlashService;
use App\Infrastructure\Persistence\Admin\PdoAdminUserRepository;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use Psr\Http\Message\ResponseInterface as Response;

final class CreateAdminUserAction extends Action
{
    protected function action(): Response
    {
        $data = (array)$this->request->getParsedBody();

        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($username === '' || $password === '') {
            FlashService::error('Veuillez remplir tous les champs.');
            return $this->redirect('/admin');
        }

        if (strlen($password) < 8) {
            FlashService::error('Le mot de passe doit contenir au moins 8 caractères.');
            return $this->redirect('/admin');
        }

        $pdo = ConnectionFactory::create();
        $repository = new PdoAdminUserRepository($pdo);

        if ($repository->findByUsername($username) !== null) {
            FlashService::error('Un administrateur avec ce nom d\'utilisateur existe déjà.');
            return $this->redirect('/admin');
        }

        $repository->create($username, $password);

        FlashService::success('Administrateur créé avec succès.');
        return $this->redirect('/admin');
    }

    private function redirect(string $url): Response
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
