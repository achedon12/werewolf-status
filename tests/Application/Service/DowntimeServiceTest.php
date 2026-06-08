<?php

declare(strict_types=1);

namespace Application\Service;

use App\Application\Service\DowntimeService;
use App\Domain\Status\Endpoint;
use Domain\Status\FakeDowntimeRepository;
use PHPUnit\Framework\TestCase;

final class DowntimeServiceTest extends TestCase
{
    public function testHttpCodeNullStartsDowntime(): void
    {
        $repository = new FakeDowntimeRepository();
        $service = new DowntimeService($repository);
        $endpoint = $this->makeEndpoint();

        $service->handleCheck($endpoint, [
            'http_code' => null,
            'error' => 'Timeout',
        ]);

        self::assertSame(1, $repository->startDowntimeCalls);
        self::assertSame(0, $repository->endDowntimeCalls);
    }

    public function testHttpCodeFiveHundredStartsDowntime(): void
    {
        $repository = new FakeDowntimeRepository();
        $service = new DowntimeService($repository);
        $endpoint = $this->makeEndpoint();

        $service->handleCheck($endpoint, [
            'http_code' => 500,
            'error' => null,
        ]);

        self::assertSame(1, $repository->startDowntimeCalls);
        self::assertSame(0, $repository->endDowntimeCalls);
    }

    public function testHttpCodeTwoHundredEndsDowntime(): void
    {
        $repository = new FakeDowntimeRepository();
        $service = new DowntimeService($repository);
        $endpoint = $this->makeEndpoint();

        $service->handleCheck($endpoint, [
            'http_code' => 200,
            'error' => null,
        ]);

        self::assertSame(0, $repository->startDowntimeCalls);
        self::assertSame(1, $repository->endDowntimeCalls);
    }

    public function testHandleCheckAddsHistoryToResult(): void
    {
        $repository = new FakeDowntimeRepository();
        $service = new DowntimeService($repository);
        $endpoint = $this->makeEndpoint();

        $result = $service->handleCheck($endpoint, [
            'http_code' => 200,
            'error' => null,
        ]);

        self::assertArrayHasKey('history', $result);
        self::assertIsArray($result['history']);
    }

    private function makeEndpoint(): Endpoint
    {
        return new Endpoint(
            1,
            'Test endpoint',
            'https://mon-endpoint.com/api/health',
            'https://mon-endpoint.com',
            'seconds',
            true,
            true,
            null
        );
    }
}
