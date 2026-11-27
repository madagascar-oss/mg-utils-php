<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Tests\Ariary;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;
use Mdgascar\MdgUtils\Ariary\SymbolPosition;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\Internal\DecimalText;
use Mdgascar\MdgUtils\MdgUtilsException;
use PHPUnit\Framework\TestCase;

final class AriaryTest extends TestCase
{
    private const int RANDOM_SEED = 2026;
    private const int FUZZ_RUNS = 2000;
    private const int FUZZ_MAX_INPUT_LENGTH = 20;
    // Largest supported amount (spec/modules/ariary/data.yaml limits.max).
    // The spec sets no max: amounts stop where an int stops.
    private const int LARGEST_SUPPORTED_AMOUNT = PHP_INT_MAX;
    // A dot is not a decimal point by default.
    private const string DOT_DECIMAL_TEXT = '1.5';
    // Every other run uses a decimal amount.
    private const int DECIMAL_EVERY = 2;
    // PHP_INT_MAX + 1 on 64-bit, written as text because no int can hold it.
    private const string FIRST_AMOUNT_TOO_LARGE = '9 223 372 036 854 775 808';
    private const string LONGER_THAN_ANY_INT = '10 000 000 000 000 000 000';
    private const string LARGEST_AMOUNT_TEXT = 'Ar 9 223 372 036 854 775 807';
    private const string SAMPLE_TEXT = '1 500 Ar';
    private const int SAMPLE_AMOUNT = 1500;

    public function testTryParseReturnsTheAmountForValidText(): void
    {
        self::assertSame(self::SAMPLE_AMOUNT, Ariary::tryParse(self::SAMPLE_TEXT)?->amount());
    }

    public function testTryParseReturnsNullInsteadOfThrowing(): void
    {
        self::assertNull(Ariary::tryParse(self::DOT_DECIMAL_TEXT));
    }

    public function testTryParseReturnsNullForNonStrings(): void
    {
        self::assertNull(Ariary::tryParse(self::SAMPLE_AMOUNT));
    }

    public function testSafeParseReturnsTheAmountOnSuccess(): void
    {
        $result = Ariary::safeParse(self::SAMPLE_TEXT);

        self::assertTrue($result->success);
        self::assertSame(self::SAMPLE_AMOUNT, $result->data?->amount());
        self::assertNull($result->error);
    }

    public function testSafeParseReturnsTheErrorCodeInsteadOfThrowing(): void
    {
        $result = Ariary::safeParse(self::DOT_DECIMAL_TEXT);

        self::assertFalse($result->success);
        self::assertNull($result->data);
        self::assertSame(ErrorCode::InvalidFormat, $result->error?->errorCode);
    }

    public function testSafeParseRejectsNonStringsAsInvalidType(): void
    {
        self::assertSame(ErrorCode::InvalidType, Ariary::safeParse(self::SAMPLE_AMOUNT)->error?->errorCode);
    }

    public function testRejectsInvalidUtf8AsInvalidFormat(): void
    {
        try {
            Ariary::parse("1\xff500");
            self::fail('Expected MdgUtilsException');
        } catch (MdgUtilsException $exception) {
            self::assertSame(ErrorCode::InvalidFormat, $exception->errorCode);
        }
    }

    public function testReadsBackEveryAmountItWritesInEveryLayout(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $amount = $run % self::DECIMAL_EVERY === 0
                ? Ariary::fromInt(mt_rand(0, self::LARGEST_SUPPORTED_AMOUNT))
                : Ariary::fromFloat(mt_rand() / mt_getrandmax() * mt_rand(0, DecimalText::MAX_EXACT_FLOAT_INTEGER));
            $grouping = Grouping::cases()[mt_rand(0, \count(Grouping::cases()) - 1)];
            $decimal = DecimalSeparator::cases()[mt_rand(0, \count(DecimalSeparator::cases()) - 1)];
            $position = SymbolPosition::cases()[mt_rand(0, \count(SymbolPosition::cases()) - 1)];
            if ($grouping->separator() === $decimal->separator()) {
                continue;
            }
            $text = $amount->format($grouping, $position, $decimal);

            self::assertSame($amount->digits, Ariary::parse($text, $grouping, $decimal)->digits);
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

    public function testReadsPhpIntMax(): void
    {
        self::assertSame(PHP_INT_MAX, Ariary::parse(self::LARGEST_AMOUNT_TEXT)->amount());
    }

    public function testKeepsAmountsAbovePhpIntMaxExactWithoutRounding(): void
    {
        foreach ([self::FIRST_AMOUNT_TOO_LARGE, self::LONGER_THAN_ANY_INT] as $text) {
            $amount = Ariary::parse($text);
            self::assertSame(preg_replace('/[^0-9]/', '', $text), $amount->digits);
            try {
                $amount->amount();
                self::fail('Expected MdgUtilsException');
            } catch (MdgUtilsException $exception) {
                self::assertSame(ErrorCode::PrecisionLoss, $exception->errorCode);
            }
        }
    }

    public function testToFmgThrowsPrecisionLossAbovePhpIntMax(): void
    {
        try {
            Ariary::fromInt(PHP_INT_MAX)->toFmg();
            self::fail('Expected MdgUtilsException');
        } catch (MdgUtilsException $exception) {
            self::assertSame(ErrorCode::PrecisionLoss, $exception->errorCode);
        }
    }
}
