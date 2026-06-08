<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use App\Application\Service\FlashService;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use Psr\Http\Message\ResponseInterface as Response;

final class LoginAdminAction extends Action
{
    protected function action(): Response
    {
        $data = (array) $this->request->getParsedBody();

        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            FlashService::error('Veuillez remplir tous les champs.');
            return $this->redirect('/admin/login');
        }

        $pdo = ConnectionFactory::create();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM admin_users
             WHERE username = :username
             AND is_enabled = 1
             LIMIT 1'
        );

        $stmt->execute([
            'username' => $username,
        ]);

        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
            FlashService::error('Identifiants incorrects.');
            return $this->redirect('/admin/login');
        }

        session_regenerate_id(true);

        $_SESSION['admin_user_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = (string) $admin['username'];
        $_SESSION['admin_role'] = (string) $admin['role'];

        return $this->redirect('/admin');
    }

    private function redirect(string $url): Response
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
