<?php

declare(strict_types=1);

use App\Application\Actions\Admin\CreateAdminUserAction;
use App\Application\Actions\Admin\CreateEndpointAction;
use App\Application\Actions\Admin\DeleteAdminUserAction;
use App\Application\Actions\Admin\DeleteEndpointAction;
use App\Application\Actions\Admin\LoginAdminAction;
use App\Application\Actions\Admin\LogoutAdminAction;
use App\Application\Actions\Admin\ToggleAdminUserAction;
use App\Application\Actions\Admin\ToggleEndpointAction;
use App\Application\Actions\Admin\UpdateAdminPasswordAction;
use App\Application\Actions\Admin\UpdateEndpointAction;
use App\Application\Actions\Admin\UpdateSettingsAction;
use App\Application\Actions\Admin\ViewAdminAction;
use App\Application\Actions\Admin\ViewAdminLoginAction;
use App\Application\Actions\Status\GetStatusAction;
use App\Application\Actions\Status\ViewStatusPageAction;
use App\Application\Middleware\AdminAuthMiddleware;
use App\Application\Service\DowntimeService;
use App\Application\Service\StatusChecker;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoDowntimeRepository;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {

    $app->options('/{routes:.*}', function (Request $request, Response $response): Response {
        return $response;
    });

    /*
    * Public status page
    */
    $app->get('/', ViewStatusPageAction::class);

    /*
     * Public API
     */
    $app->get('/api/status', GetStatusAction::class);

    $app->get('/admin/login', ViewAdminLoginAction::class);
    $app->post('/admin/login', LoginAdminAction::class);
    $app->post('/admin/logout', LogoutAdminAction::class);

    $app->group('/admin', function ($group): void {
        $group->get('', ViewAdminAction::class);

        $group->post('/settings', UpdateSettingsAction::class);

        $group->post('/endpoints', CreateEndpointAction::class);
        $group->post('/endpoints/{id}/update', UpdateEndpointAction::class);
        $group->post('/endpoints/{id}/delete', DeleteEndpointAction::class);
        $group->post('/endpoints/{id}/toggle', ToggleEndpointAction::class);

        $group->post('/admins', CreateAdminUserAction::class);
        $group->post('/admins/{id:[0-9]+}/password', UpdateAdminPasswordAction::class);
        $group->post('/admins/{id:[0-9]+}/toggle', ToggleAdminUserAction::class);
        $group->post('/admins/{id:[0-9]+}/delete', DeleteAdminUserAction::class);
    })->add(AdminAuthMiddleware::class);

};
