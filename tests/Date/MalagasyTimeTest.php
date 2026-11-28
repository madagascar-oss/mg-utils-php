<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Tests\Date;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\CurrencyDisplay;
use Mdgascar\MdgUtils\Date\MalagasyTime;
use Mdgascar\MdgUtils\ErrorCode;
use PHPUnit\Framework\TestCase;

final class MalagasyTimeTest extends TestCase
{
    private const int HOURS_PER_DAY = 24;
    private const int MINUTES_PER_HOUR = 60;
    private const int AFTERNOON_HOUR = 14;
    private const int HALF_HOUR = 30;
    private const string HALF_PAST_TWO = "amin'ny roa sy sasany tolakandro";
    private const int TOO_LONG_REPEATS = 60;
    private const int SAMPLE_AMOUNT = 1500;

    public function testReadsBackEveryTimeOfTheDay(): void
    {
        for ($hour = 0; $hour < self::HOURS_PER_DAY; ++$hour) {
            for ($minute = 0; $minute < self::MINUTES_PER_HOUR; ++$minute) {
                $time = MalagasyTime::fromParts($hour, $minute);
                self::assertEquals($time, MalagasyTime::parse($time->spell()));
            }
        }
    }

    public function testReadsAPhpDateInItsOwnTimeZone(): void
    {
        $date = new \DateTimeImmutable('2026-11-27 14:30:00');

        self::assertSame(self::HALF_PAST_TWO, MalagasyTime::fromDateTime($date)->spell());
    }

    public function testSafeParseAndTryParseNeverThrow(): void
    {
        self::assertSame(self::AFTERNOON_HOUR, MalagasyTime::safeParse(self::HALF_PAST_TWO)->data?->hour);
        self::assertSame(ErrorCode::InvalidType, MalagasyTime::safeParse(self::HALF_HOUR)->error?->errorCode);
        self::assertSame(self::HALF_HOUR, MalagasyTime::tryParse(self::HALF_PAST_TWO)?->minute);
        self::assertNull(MalagasyTime::tryParse('two thirty'));
        self::assertSame(ErrorCode::InvalidFormat, MalagasyTime::safeParse(str_repeat('roa ', self::TOO_LONG_REPEATS))->error?->errorCode);
    }

    public function testFormatsAnAmountWithTheIsoCode(): void
    {
        self::assertSame("MGA\u{00A0}1\u{00A0}500", Ariary::fromInt(self::SAMPLE_AMOUNT)->format(currency: CurrencyDisplay::Code));
        self::assertSame("MGF\u{00A0}1\u{00A0}500", Ariary::formatFmgCode(self::SAMPLE_AMOUNT));
    }
}
