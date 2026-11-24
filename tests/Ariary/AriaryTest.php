<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Tests\Ariary;

use Madagascar\MgUtils\Ariary\Ariary;
use Madagascar\MgUtils\Ariary\Grouping;
use Madagascar\MgUtils\Ariary\SymbolPosition;
use Madagascar\MgUtils\ErrorCode;
use Madagascar\MgUtils\MgUtilsException;
use PHPUnit\Framework\TestCase;

final class AriaryTest extends TestCase
{
    private const int RANDOM_SEED = 2026;
    private const int FUZZ_RUNS = 2000;
    private const int FUZZ_MAX_INPUT_LENGTH = 20;
    // Largest supported amount (spec/ariary/data.yaml limits.max).
    private const int LARGEST_SUPPORTED_AMOUNT = 999_999_999_999;
    private const string SAMPLE_TEXT = '1 500 Ar';
    private const int SAMPLE_AMOUNT = 1500;

    public function testTryParseReturnsTheAmountForValidText(): void
    {
        self::assertSame(self::SAMPLE_AMOUNT, Ariary::tryParse(self::SAMPLE_TEXT)?->amount);
    }

    public function testTryParseReturnsNullInsteadOfThrowing(): void
    {
        self::assertNull(Ariary::tryParse('1,500'));
    }

    public function testRejectsInvalidUtf8AsInvalidFormat(): void
    {
        try {
            Ariary::parse("1\xff500");
            self::fail('Expected MgUtilsException');
        } catch (MgUtilsException $exception) {
            self::assertSame(ErrorCode::InvalidFormat, $exception->errorCode);
        }
    }

    public function testReadsBackEveryAmountItWritesInEveryLayout(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $amount = Ariary::fromInt(mt_rand(0, self::LARGEST_SUPPORTED_AMOUNT));
            $grouping = Grouping::cases()[mt_rand(0, \count(Grouping::cases()) - 1)];
            $position = SymbolPosition::cases()[mt_rand(0, \count(SymbolPosition::cases()) - 1)];

            self::assertSame($amount->amount, Ariary::parse($amount->format($grouping, $position))->amount);
        }
    }

    public function testNeverThrowsForRandomStrings(): void
    {
        mt_srand(self::RANDOM_SEED);
        $characters = preg_split('//u', "0123456789 .,-Ar MGAariy\u{a0}\u{202f}€", -1, \PREG_SPLIT_NO_EMPTY);
        self::assertIsArray($characters);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $text = '';
            for ($index = mt_rand(0, self::FUZZ_MAX_INPUT_LENGTH); $index > 0; --$index) {
                $text .= $characters[mt_rand(0, \count($characters) - 1)];
            }

            self::assertThat(Ariary::tryParse($text), self::logicalOr(self::isNull(), self::isInstanceOf(Ariary::class)));
        }
    }
}
