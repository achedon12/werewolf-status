<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ViewAdminLoginAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        if (!empty($_SESSION['admin_user_id'])) {
            return $response
                ->withHeader('Location', '/admin')
                ->withStatus(302);
        }

        $viewFile = __DIR__ . '/../../../../app/views/admin/login.php';

        if (!file_exists($viewFile)) {
            $response->getBody()->write('Vue login admin introuvable');
            return $response->withStatus(500);
        }

        ob_start();
        require $viewFile;
        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }
}