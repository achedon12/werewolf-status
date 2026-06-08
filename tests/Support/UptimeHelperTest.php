<?php

declare(strict_types=1);

namespace Support;

use App\Application\Support\UptimeHelper;
use PHPUnit\Framework\TestCase;

final class UptimeHelperTest extends TestCase
{
    public function testSecondsUptimeReturnsStartedTimestamp(): void
    {
        $now = time();

        $result = UptimeHelper::toStartedTimestamp(3600, 'seconds');

        self::assertNotNull($result);
        self::assertGreaterThanOrEqual($now - 3601, $result);
        self::assertLessThanOrEqual($now - 3599, $result);
    }

    public function testMillisecondsUptimeReturnsStartedTimestamp(): void
    {
        $now = time();

        $result = UptimeHelper::toStartedTimestamp(3600000, 'milliseconds');

        self::assertNotNull($result);
        self::assertGreaterThanOrEqual($now - 3601, $result);
        self::assertLessThanOrEqual($now - 3599, $result);
    }

    public function testTimestampSecondsReturnsSameTimestamp(): void
    {
        $timestamp = time() - 120;

        $result = UptimeHelper::toStartedTimestamp($timestamp, 'timestamp_seconds');

        self::assertSame($timestamp, $result);
    }

    public function testTimestampMillisecondsReturnsTimestampInSeconds(): void
    {
        $timestamp = time() - 120;

        $result = UptimeHelper::toStartedTimestamp($timestamp * 1000, 'timestamp_milliseconds');

        self::assertSame($timestamp, $result);
    }

    public function testNullUptimeReturnsNull(): void
    {
        self::assertNull(UptimeHelper::toStartedTimestamp(null));
    }

    public function testZeroUptimeReturnsNull(): void
    {
        self::assertNull(UptimeHelper::toStartedTimestamp(0));
    }

    public function testNegativeUptimeReturnsNull(): void
    {
        self::assertNull(UptimeHelper::toStartedTimestamp(-1));
    }

    public function testTextDurationReturnsStartedTimestamp(): void
    {
        $now = time();

        $result = UptimeHelper::toStartedTimestamp('1h 30m');

        self::assertNotNull($result);
        self::assertGreaterThanOrEqual($now - 5401, $result);
        self::assertLessThanOrEqual($now - 5399, $result);
    }

    public function testFormatDurationReturnsSeconds(): void
    {
        self::assertSame('45s', UptimeHelper::formatDuration(time() - 45));
    }

    public function testFormatDurationReturnsMinutesAndSeconds(): void
    {
        self::assertSame('2m 5s', UptimeHelper::formatDuration(time() - 125));
    }

    public function testFormatDurationReturnsHoursAndMinutes(): void
    {
        self::assertSame('1h 2m', UptimeHelper::formatDuration(time() - 3720));
    }

    public function testFormatDurationReturnsDaysAndHours(): void
    {
        self::assertSame('1j 2h', UptimeHelper::formatDuration(time() - 93600));
    }
}
