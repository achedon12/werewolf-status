<?php

declare(strict_types=1);

namespace App\Application\Actions\Status;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\Status\StatusSnapshotRepository;
use Psr\Http\Message\ResponseInterface as Response;

final class GetStatusAction extends Action
{
    protected function action(): Response
    {
        $snapshotRepository = new StatusSnapshotRepository(
            __DIR__ . '/../../../../var/cache/status_snapshot.json'
        );

        $payload = $snapshotRepository->get();

        if ($payload === null) {
            return $this->respondWithData([
                'results' => [],
                'infos' => [
                    'json' => null,
                    'error' => 'Cache status non généré.',
                ],
                'generated_at' => null,
                'cached_at' => null,
            ], 503);
        }

        return $this->respondWithData($payload);
    }
}