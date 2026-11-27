<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Tests\Date;

use Mdgascar\MdgUtils\Date\DateStyle;
use Mdgascar\MdgUtils\Date\MalagasyDate;
use Mdgascar\MdgUtils\ErrorCode;
use PHPUnit\Framework\TestCase;

final class ParseDateTest extends TestCase
{
    private const int RANDOM_SEED = 2026;
    private const int FUZZ_RUNS = 500;
    // From year 32 no year can pass for a day, so no two styles read one text differently.
    private const int FIRST_YEAR_NO_STYLE_COLLIDES = 32;
    private const int MAX_YEAR = 9999;
    private const int MONTHS = 12;
    private const int DAYS_IN_SHORTEST_MONTH = 28;
    private const int SAMPLE_DAY = 23;

    public function testReadsBackEveryDateFormatWritesInEveryStyle(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $date = MalagasyDate::fromParts(
                mt_rand(self::FIRST_YEAR_NO_STYLE_COLLIDES, self::MAX_YEAR),
                mt_rand(1, self::MONTHS),
                mt_rand(1, self::DAYS_IN_SHORTEST_MONTH),
            );
            foreach (DateStyle::cases() as $style) {
                self::assertEquals($date, MalagasyDate::parse($date->format($style)));
            }
        }
    }

    public function testSafeParseAndTryParseNeverThrow(): void
    {
        self::assertSame(self::SAMPLE_DAY, MalagasyDate::safeParse('23/11/2026')->data?->day);
        self::assertSame(ErrorCode::InvalidType, MalagasyDate::safeParse(self::SAMPLE_DAY)->error?->errorCode);
        self::assertSame(ErrorCode::InvalidFormat, MalagasyDate::safeParse('30/02/2026')->error?->errorCode);
        self::assertNull(MalagasyDate::tryParse('30/02/2026'));
        self::assertSame(self::SAMPLE_DAY, MalagasyDate::tryParse('23 Novambra 2026')?->day);
    }
}
