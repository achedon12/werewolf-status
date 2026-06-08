<?php

declare(strict_types=1);

namespace Application\Service;

use App\Application\Service\StatusChecker;
use PHPUnit\Framework\TestCase;

final class StatusCheckerTest extends TestCase
{
    public function testInvalidUrlReturnsError(): void
    {
        $checker = new StatusChecker();

        $result = $checker->check('not-a-valid-url');

        self::assertIsArray($result);
        self::assertArrayHasKey('error', $result);
    }

    public function testUnreachableUrlReturnsError(): void
    {
        $checker = new StatusChecker();

        $result = $checker->check('https://invalid.invalid/api/health');

        self::assertIsArray($result);
        self::assertArrayHasKey('error', $result);
    }
}
