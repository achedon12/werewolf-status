<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

final class AdminAuthMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!empty($_SESSION['admin_user_id'])) {
            return $handler->handle($request);
        }

        $response = new SlimResponse();

        return $response
            ->withHeader('Location', '/admin/login')
            ->withStatus(302);
    }
}
