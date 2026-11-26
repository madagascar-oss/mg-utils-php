<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Tests\Date;

use Mdgascar\MdgUtils\Date\DateStyle;
use Mdgascar\MdgUtils\Date\MalagasyDate;
use PHPUnit\Framework\TestCase;

final class MalagasyDateTest extends TestCase
{
    private const int RANDOM_SEED = 2026;
    private const int FUZZ_RUNS = 2000;
    // Four-digit years, so the CLDR short style (y-MM-dd) writes them exactly as ISO text.
    private const int FIRST_FOUR_DIGIT_YEAR = 1000;
    private const int LAST_SUPPORTED_YEAR = 9999;
    private const int DECEMBER = 12;
    private const int LAST_DAY_OF_DECEMBER = 31;
    private const string ISO_FORMAT = 'Y-m-d';
    private const string INDEPENDENCE_DAY = '1960-06-26';

    public function testReadsTheCalendarFieldsOfAPhpDate(): void
    {
        $date = new \DateTimeImmutable(self::INDEPENDENCE_DAY);

        self::assertSame('Alahady 26 Jona 1960', MalagasyDate::fromDateTime($date)->format(DateStyle::Full));
    }

    public function testWritesTheShortStyleExactlyAsTheIsoTextItReads(): void
    {
        mt_srand(self::RANDOM_SEED);
        $first = (new \DateTimeImmutable())->setDate(self::FIRST_FOUR_DIGIT_YEAR, 1, 1)->getTimestamp();
        $last = (new \DateTimeImmutable())->setDate(self::LAST_SUPPORTED_YEAR, self::DECEMBER, self::LAST_DAY_OF_DECEMBER)->getTimestamp();
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $text = (new \DateTimeImmutable())->setTimestamp(mt_rand($first, $last))->format(self::ISO_FORMAT);

            self::assertSame($text, MalagasyDate::fromString($text)->format(DateStyle::Short));
        }
    }
}
