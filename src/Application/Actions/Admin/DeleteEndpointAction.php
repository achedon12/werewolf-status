<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use App\Application\Service\FlashService;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use Psr\Http\Message\ResponseInterface as Response;

final class DeleteEndpointAction extends Action
{
    protected function action(): Response
    {
        $endpointId = (int) ($this->args['id'] ?? 0);

        if ($endpointId <= 0) {
            FlashService::error('Endpoint invalide.');
            return $this->redirect('/admin');
        }

        $pdo = ConnectionFactory::create();
        $endpointRepository = new PdoEndpointRepository($pdo);

        $endpointRepository->delete($endpointId);

        FlashService::success('Endpoint supprimé avec succès.');
        return $this->redirect('/admin');
    }

    private function redirect(string $url): Response
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
