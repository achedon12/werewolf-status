<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Actions\Action;
use App\Application\Service\FlashService;
use App\Infrastructure\Persistence\Database\ConnectionFactory;
use App\Infrastructure\Persistence\Status\PdoEndpointRepository;
use Psr\Http\Message\ResponseInterface as Response;

final class CreateEndpointAction extends Action
{
    private const ALLOWED_UPTIME_UNITS = [
        'seconds',
        'milliseconds'
    ];

    protected function action(): Response
    {
        $data = (array) $this->request->getParsedBody();

        $name = trim((string) ($data['name'] ?? ''));
        $checkUrl = trim((string) ($data['check_url'] ?? ''));
        $publicUrl = trim((string) ($data['public_url'] ?? ''));
        $uptimeUnit = (string) ($data['uptime_unit'] ?? 'seconds');
        $discordEnabled = isset($data['discord_notifications_enabled']);
        $discordWebhookUrl = trim((string) ($data['discord_webhook_url'] ?? ''));

        if ($name === '' || $checkUrl === '') {
            FlashService::error('Veuillez remplir tous les champs obligatoires.');
            return $this->redirect('/admin');
        }

        if (!filter_var($checkUrl, FILTER_VALIDATE_URL)) {
            FlashService::error('L\'URL de vérification est invalide.');
            return $this->redirect('/admin');
        }

        if ($publicUrl !== '' && !filter_var($publicUrl, FILTER_VALIDATE_URL)) {
            FlashService::error('L\'URL publique est invalide.');
            return $this->redirect('/admin');
        }

        if (!in_array($uptimeUnit, self::ALLOWED_UPTIME_UNITS, true)) {
            FlashService::error('L\'unité de temps d\'activité est invalide.');
            return $this->redirect('/admin');
        }

        if ($discordWebhookUrl !== '' && !filter_var($discordWebhookUrl, FILTER_VALIDATE_URL)) {
            FlashService::error('Webhook Discord invalide.');
            return $this->redirect('/admin');
        }

        $pdo = ConnectionFactory::create();
        $endpointRepository = new PdoEndpointRepository($pdo);

        $endpointRepository->create(
            $name,
            $checkUrl,
            $publicUrl !== '' ? $publicUrl : null,
            $uptimeUnit,
            $discordEnabled,
            $discordWebhookUrl !== '' ? $discordWebhookUrl : null
        );

        FlashService::success('Endpoint créé avec succès.');
        return $this->redirect('/admin');
    }

    private function redirect(string $url): Response
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}