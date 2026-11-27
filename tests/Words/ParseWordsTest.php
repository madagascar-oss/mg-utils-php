<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Tests\Words;

use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\Words\NumberSystem;
use Mdgascar\MdgUtils\Words\NumberWords;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParseWordsTest extends TestCase
{
    private const int RANDOM_SEED = 2027;
    private const int FUZZ_RUNS = 300;
    private const int TWO_THOUSAND = 2000;
    private const string TWO_THOUSAND_DIGITS = '2000';
    // Past the 4096-character limit.
    private const int TOO_LONG_REPEATS = 1100;

    public function testReadsBackEveryNumberSpellWritesInBothSystems(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $number = mt_rand(0, PHP_INT_MAX);
            foreach (NumberSystem::cases() as $system) {
                self::assertSame($number, NumberWords::parse(NumberWords::spell($number, $system))->value());
            }
        }
    }

    public function testSafeParseAndTryParseNeverThrow(): void
    {
        self::assertSame(self::TWO_THOUSAND, NumberWords::safeParse('roa arivo')->data?->value());
        self::assertSame(ErrorCode::InvalidType, NumberWords::safeParse(self::TWO_THOUSAND)->error?->errorCode);
        self::assertSame(ErrorCode::InvalidFormat, NumberWords::safeParse('roa sy')->error?->errorCode);
        self::assertNull(NumberWords::tryParse('roa sy'));
        self::assertSame(self::TWO_THOUSAND_DIGITS, NumberWords::tryParse('roa arivo')?->digits);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function wordsSpellNeverWrites(): iterable
    {
        yield 'two decimal words' => ['iray faingo roa faingo telo'];
        yield 'only zeros after faingo' => ['iray faingo aotra'];
        yield 'a smaller part larger than its group' => ['dimy alina amby roa arivo lavitrisa'];
        yield 'a traditional name inside a count' => ['roa safatsiroa lavitrisa'];
        yield 'longer than the limit' => [str_repeat('roa ', self::TOO_LONG_REPEATS)];
    }

    #[DataProvider('wordsSpellNeverWrites')]
    public function testRejectsWordsSpellNeverWrites(string $words): void
    {
        self::assertSame(ErrorCode::InvalidFormat, NumberWords::safeParse($words)->error?->errorCode);
    }
}
