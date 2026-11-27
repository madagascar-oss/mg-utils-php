<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Tests\Words;

use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\Words\NumberSystem;
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
    private const string PHP_INT_MAX_WORDS = 'fito sy valonjato sy dimy arivo sy fito alina sy fito hetsy sy efatra amby dimampolo amby valonjato tapitrisa sy enina amby telopolo amby roa arivo amby fito alina amby telo hetsy amby telo amby roapolo amby roanjato tapitrisa amby sivy lavitrisa lavitrisa';

    // The same in the traditional system (rule 12), up to the derived 10¹⁸ name.
    private const string PHP_INT_MAX_TRADITIONAL_WORDS = 'fito sy valonjato sy dimy arivo sy fito alina sy fito hetsy sy efatra tapitrisa sy dimy safatsiroa sy valo tsitamboisa sy enina lavitrisa sy telo alinkisa sy roa tsitokotsiforohana sy fito tsihitanoanoa sy telo safatsiroafaharoa sy telo tsitamboisafaharoa sy roa lavitrisafaharoa sy roa alinkisafaharoa sy sivy tsipesimpeseninafaharoa';
    // Rule 13: a float this large keeps no decimals; text keeps them.
    private const string EXACT_TEXT = '2000000000000000.9';
    private const string EXACT_TEXT_WORDS = 'roa tapitrisa lavitrisa faingo sivy';
    private const int TEN_TO_THE_16 = 10_000_000_000_000_000;
    private const int TEN_TO_THE_17 = 100_000_000_000_000_000;
    private const int TEN_TO_THE_18 = 1_000_000_000_000_000_000;
    // Traditional groups, split on the joiner between groups.
    private const string GROUP_JOINER = ' sy ';
    // A tens, hundreds or thousand word right before a power name: a count above 9.
    private const string COUNT_ABOVE_NINE = '/(polo|folo|zato|jato|arivo) (alina|hetsy|tapitrisa|safatsiroa|tsitamboisa|lavitrisa|alinkisa|tsipesimpesenina|tsitokotsiforohana|tsihitanoanoa)(faharoa)?$/';

    public function testReturnsWordsForRandomIntegers(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $words = NumberWords::spell(mt_rand(0, PHP_INT_MAX));

            self::assertMatchesRegularExpression(self::WORDS_ONLY, $words);
            self::assertDoesNotMatchRegularExpression(self::BROKEN_JOIN, $words);
        }
    }

    public function testReturnsWordsForRandomFloats(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $value = mt_rand() / mt_getrandmax() * mt_rand(1, self::MAX_EXACT_FLOAT_INTEGER);
            $words = NumberWords::spell($value);

            self::assertMatchesRegularExpression(self::WORDS_ONLY, $words);
            self::assertDoesNotMatchRegularExpression(self::BROKEN_JOIN, $words);
            self::assertSame(floor($value) !== $value, str_contains($words, self::DECIMAL_SEPARATOR));
        }
    }

    public function testSpellsPhpIntMax(): void
    {
        self::assertSame(self::PHP_INT_MAX_WORDS, NumberWords::spell(PHP_INT_MAX));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function derivedTraditionalNames(): iterable
    {
        yield '10^16' => [self::TEN_TO_THE_16, 'iray lavitrisafaharoa'];
        yield '10^17' => [self::TEN_TO_THE_17, 'iray alinkisafaharoa'];
        yield '10^18' => [self::TEN_TO_THE_18, 'iray tsipesimpeseninafaharoa'];
    }

    #[DataProvider('derivedTraditionalNames')]
    public function testUsesTheDerivedTraditionalNamesBeyondTheSharedVectors(int $value, string $words): void
    {
        self::assertSame($words, NumberWords::spell($value, NumberSystem::Traditional));
    }

    public function testSpellsPhpIntMaxInTheTraditionalSystem(): void
    {
        self::assertSame(self::PHP_INT_MAX_TRADITIONAL_WORDS, NumberWords::spell(PHP_INT_MAX, NumberSystem::Traditional));
    }

    public function testEveryTraditionalPowerTakesASingleDigit(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $words = NumberWords::spell(mt_rand(0, PHP_INT_MAX), NumberSystem::Traditional);

            self::assertMatchesRegularExpression(self::WORDS_ONLY, $words);
            self::assertDoesNotMatchRegularExpression(self::BROKEN_JOIN, $words);
            foreach (explode(self::GROUP_JOINER, $words) as $group) {
                self::assertDoesNotMatchRegularExpression(self::COUNT_ABOVE_NINE, $group);
            }
        }
    }

    public function testTextAndIntAgreeOnEveryInteger(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $value = mt_rand(0, PHP_INT_MAX);

            self::assertSame(NumberWords::spell($value), NumberWords::spell((string) $value));
        }
    }

    public function testTextKeepsTheDecimalsAFloatLoses(): void
    {
        self::assertSame(self::EXACT_TEXT_WORDS, NumberWords::spell(self::EXACT_TEXT));
        self::assertNotSame(self::EXACT_TEXT_WORDS, NumberWords::spell((float) self::EXACT_TEXT));
    }

    public function testFloatAndIntAgreeOnWholeNumbers(): void
    {
        self::assertSame(
            NumberWords::spell(self::MAX_EXACT_FLOAT_INTEGER),
            NumberWords::spell((float) self::MAX_EXACT_FLOAT_INTEGER),
        );
    }

    /**
     * @return iterable<string, array{callable(): string, ErrorCode}>
     */
    public static function rejectedInputs(): iterable
    {
        yield 'negative int' => [static fn(): string => NumberWords::spell(-1), ErrorCode::OutOfRange];
        yield 'negative float' => [static fn(): string => NumberWords::spell(self::NEGATIVE_DECIMAL), ErrorCode::OutOfRange];
        yield 'float above exact integers' => [
            static fn(): string => NumberWords::spell((float) PHP_INT_MAX),
            ErrorCode::OutOfRange,
        ];
        yield 'NAN' => [static fn(): string => NumberWords::spell(NAN), ErrorCode::InvalidType];
        yield 'INF' => [static fn(): string => NumberWords::spell(INF), ErrorCode::InvalidType];
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
