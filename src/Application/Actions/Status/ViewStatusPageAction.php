<?php

declare(strict_types=1);

namespace App\Application\Actions\Status;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\Status\StatusSnapshotRepository;
use Psr\Http\Message\ResponseInterface as Response;

final class ViewStatusPageAction extends Action
{
    protected function action(): Response
    {
        $snapshotRepository = new StatusSnapshotRepository(
            __DIR__ . '/../../../../var/cache/status_snapshot.json'
        );

        $payload = $snapshotRepository->get();

        $results = $payload['results'] ?? [];
        $infos = $payload['infos'] ?? [
            'json' => null,
            'error' => 'Cache status non généré.',
        ];

        $cachedAt = $payload['cached_at'] ?? null;
        $generatedAt = $payload['generated_at'] ?? null;

        $viewFile = __DIR__ . '/../../../../app/views/status.php';

        if (!file_exists($viewFile)) {
            $this->response->getBody()->write('Vue status introuvable : ' . $viewFile);
            return $this->response->withStatus(500);
        }

        ob_start();
        require $viewFile;
        $html = ob_get_clean();

        $this->response->getBody()->write($html);

        return $this->response;
    }
}
