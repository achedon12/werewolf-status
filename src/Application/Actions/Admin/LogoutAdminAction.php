<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;

final class LogoutAdminAction extends Action
{
    protected function action(): Response
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return $this->response
            ->withHeader('Location', '/admin/login')
            ->withStatus(302);
    }
}
