<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

final class SessionMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'httponly' => true,
                'secure' => ($_ENV['SESSION_SECURE'] ?? 'false') === 'true',
                'samesite' => 'Lax',
            ]);

            session_start();
        }

        $request = $request->withAttribute('session', $_SESSION);

        return $handler->handle($request);
    }
}
