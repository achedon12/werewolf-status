<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Status;

use App\Infrastructure\Persistence\Status\StatusSnapshotRepository;
use PHPUnit\Framework\TestCase;

final class StatusSnapshotRepositoryTest extends TestCase
{
    private string $tempDirectory;
    private string $snapshotFile;

    protected function setUp(): void
    {
        $this->tempDirectory = sys_get_temp_dir() . '/status_snapshot_test_' . uniqid('', true);
        $this->snapshotFile = $this->tempDirectory . '/status_snapshot.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->snapshotFile)) {
            unlink($this->snapshotFile);
        }

        if (is_dir($this->tempDirectory)) {
            rmdir($this->tempDirectory);
        }
    }

    public function testGetReturnsNullWhenFileDoesNotExist(): void
    {
        $repository = new StatusSnapshotRepository($this->snapshotFile);

        self::assertNull($repository->get());
    }

    public function testGetReturnsNullWhenJsonIsInvalid(): void
    {
        mkdir($this->tempDirectory, 0775, true);
        file_put_contents($this->snapshotFile, '{invalid json');

        $repository = new StatusSnapshotRepository($this->snapshotFile);

        self::assertNull($repository->get());
    }

    public function testSaveCreatesSnapshotFile(): void
    {
        $repository = new StatusSnapshotRepository($this->snapshotFile);

        $repository->save([
            'results' => [],
            'infos' => [
                'json' => null,
                'error' => null,
            ],
            'generated_at' => '2026-01-01 12:00:00',
        ]);

        self::assertFileExists($this->snapshotFile);
    }

    public function testSaveAddsCachedAt(): void
    {
        $repository = new StatusSnapshotRepository($this->snapshotFile);

        $repository->save([
            'results' => [],
        ]);

        $data = $repository->get();

        self::assertIsArray($data);
        self::assertArrayHasKey('cached_at', $data);
    }

    public function testGetReturnsSavedPayload(): void
    {
        $repository = new StatusSnapshotRepository($this->snapshotFile);

        $repository->save([
            'results' => [
                'Test' => [
                    'http_code' => 200,
                ],
            ],
        ]);

        $data = $repository->get();

        self::assertIsArray($data);
        self::assertArrayHasKey('results', $data);
        self::assertSame(200, $data['results']['Test']['http_code']);
    }
}
