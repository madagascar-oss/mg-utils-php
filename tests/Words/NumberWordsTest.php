<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Tests\Words;

use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\Words\NumberWords;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NumberWordsTest extends TestCase
{
    private const int RANDOM_SEED = 2026;
    private const int FUZZ_RUNS = 2000;
    private const string WORDS_ONLY = "/^[a-z' ]+$/";
    private const string BROKEN_JOIN = '/ {2}|\b(sy|amby|faingo) \1\b|^ | $/';
    private const string DECIMAL_SEPARATOR = 'faingo';
    private const float NEGATIVE_DECIMAL = -0.5;
    // Largest integer a float holds exactly (JavaScript's Number.MAX_SAFE_INTEGER).
    private const int MAX_EXACT_FLOAT_INTEGER = 9_007_199_254_740_991;
    // PHP_INT_MAX on 64-bit, beyond the shared vectors: 9 223 372 036 × 10⁹ + 854 775 807.
    private const string PHP_INT_MAX_WORDS = 'fito sy valonjato sy dimy arivo sy fito alina sy fito hetsy sy efatra amby dimampolo sy valonjato tapitrisa sy enina amby telopolo sy roa arivo sy fito alina sy telo hetsy sy telo amby roapolo sy roanjato tapitrisa sy sivy lavitrisa lavitrisa';

    public function testReturnsWordsForRandomIntegers(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $words = NumberWords::fromInt(mt_rand(0, PHP_INT_MAX));

            self::assertMatchesRegularExpression(self::WORDS_ONLY, $words);
            self::assertDoesNotMatchRegularExpression(self::BROKEN_JOIN, $words);
        }
    }

    public function testReturnsWordsForRandomFloats(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $value = mt_rand() / mt_getrandmax() * mt_rand(1, self::MAX_EXACT_FLOAT_INTEGER);
            $words = NumberWords::fromFloat($value);

            self::assertMatchesRegularExpression(self::WORDS_ONLY, $words);
            self::assertDoesNotMatchRegularExpression(self::BROKEN_JOIN, $words);
            self::assertSame(floor($value) !== $value, str_contains($words, self::DECIMAL_SEPARATOR));
        }
    }

    public function testSpellsPhpIntMax(): void
    {
        self::assertSame(self::PHP_INT_MAX_WORDS, NumberWords::fromInt(PHP_INT_MAX));
    }

    public function testFloatAndIntAgreeOnWholeNumbers(): void
    {
        self::assertSame(
            NumberWords::fromInt(self::MAX_EXACT_FLOAT_INTEGER),
            NumberWords::fromFloat((float) self::MAX_EXACT_FLOAT_INTEGER),
        );
    }

    /**
     * @return iterable<string, array{callable(): string, ErrorCode}>
     */
    public static function rejectedInputs(): iterable
    {
        yield 'negative int' => [static fn(): string => NumberWords::fromInt(-1), ErrorCode::OutOfRange];
        yield 'negative float' => [static fn(): string => NumberWords::fromFloat(self::NEGATIVE_DECIMAL), ErrorCode::OutOfRange];
        yield 'float above exact integers' => [
            static fn(): string => NumberWords::fromFloat((float) PHP_INT_MAX),
            ErrorCode::OutOfRange,
        ];
        yield 'NAN' => [static fn(): string => NumberWords::fromFloat(NAN), ErrorCode::InvalidType];
        yield 'INF' => [static fn(): string => NumberWords::fromFloat(INF), ErrorCode::InvalidType];
    }

    /**
     * @param callable(): string $call
     */
    #[DataProvider('rejectedInputs')]
    public function testRejectsWithTheSharedCode(callable $call, ErrorCode $code): void
    {
        try {
            $call();
            self::fail('Expected MdgUtilsException');
        } catch (MdgUtilsException $exception) {
            self::assertSame($code, $exception->errorCode);
        }
    }
}
