<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Words;

use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\Internal\DecimalText;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\Words\Generated\Data;

/**
 * Malagasy number words.
 */
final class NumberWords
{
    private const int BASE = 10;
    private const int HUNDRED = self::BASE * self::BASE;
    private const int BELOW_THOUSAND_DIGITS = 3;
    private const string ONE = '1';
    private const string ZERO_DIGIT = '0';
    private const string DECIMAL_POINT = '.';

    /**
     * Writes a whole number in Malagasy words, smallest part first.
     *
     * Example: `NumberWords::fromInt(2026)` returns `'enina amby roapolo sy roa arivo'`;
     * `NumberWords::fromInt(30_000_000, NumberSystem::Traditional)` returns `'telo safatsiroa'`.
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` below 0
     */
    public static function fromInt(int $value, NumberSystem $system = NumberSystem::Standard): string
    {
        if ($value < Data::MIN_VALUE) {
            throw self::outOfRange(PHP_INT_MAX);
        }

        return self::wholeWords((string) $value, self::scales($system));
    }

    /**
     * Writes a number in Malagasy words, with `faingo` before the decimals, which are read as
     * a whole number (each leading zero is `aotra`). The decimals are those of the shortest
     * form that reads back as the same float, so `1.0` has none.
     *
     * Example: `NumberWords::fromFloat(3.14)` returns `"telo faingo efatra ambin'ny folo"`.
     * In `NumberSystem::Traditional` the decimals are read in the same system.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidType` for NAN or INF,
     *                          `ErrorCode::OutOfRange` below 0 or above 9 007 199 254 740 991
     */
    public static function fromFloat(float $value, NumberSystem $system = NumberSystem::Standard): string
    {
        if (!is_finite($value)) {
            throw new MdgUtilsException(ErrorCode::InvalidType, 'NumberWords expects a finite number.');
        }
        if ($value < Data::MIN_VALUE || $value > DecimalText::MAX_EXACT_FLOAT_INTEGER) {
            throw self::outOfRange(DecimalText::MAX_EXACT_FLOAT_INTEGER);
        }
        $scales = self::scales($system);
        $parts = explode(self::DECIMAL_POINT, DecimalText::plain($value));
        $words = self::wholeWords($parts[0], $scales);
        if (!isset($parts[1])) {
            return $words;
        }

        return \sprintf('%s %s %s', $words, Data::DECIMAL_SEPARATOR, self::decimalWords($parts[1], $scales));
    }

    /**
     * @return non-empty-list<array{word: string, exponent: int, countDigits: int|null}>
     */
    private static function scales(NumberSystem $system): array
    {
        return match ($system) {
            NumberSystem::Standard => Data::SCALES,
            NumberSystem::Traditional => Data::TRADITIONAL_SCALES,
        };
    }

    private static function outOfRange(int $max): MdgUtilsException
    {
        return new MdgUtilsException(
            ErrorCode::OutOfRange,
            \sprintf('NumberWords supports %d to %d.', Data::MIN_VALUE, $max),
        );
    }

    /**
     * Works on decimal digits, not arithmetic, so counts and decimals of any length stay exact.
     *
     * @param non-empty-list<array{word: string, exponent: int, countDigits: int|null}> $scales
     */
    private static function wholeWords(string $digits, array $scales): string
    {
        $significant = ltrim($digits, self::ZERO_DIGIT);
        if ($significant === '') {
            return Data::ZERO_WORD;
        }
        $groups = [self::belowThousand((int) substr($significant, -self::BELOW_THOUSAND_DIGITS))];
        foreach ($scales as $scale) {
            $groups[] = self::scaleWords($significant, $scale, $scales);
        }

        return self::join($groups, Data::JOINER_BETWEEN_GROUPS);
    }

    /**
     * Each leading zero after the point is "aotra"; the rest reads as a whole number.
     *
     * @param non-empty-list<array{word: string, exponent: int, countDigits: int|null}> $scales
     */
    private static function decimalWords(string $digits, array $scales): string
    {
        $significant = ltrim($digits, self::ZERO_DIGIT);
        $zeros = array_fill(0, \strlen($digits) - \strlen($significant), Data::ZERO_WORD);

        return implode(' ', [...$zeros, self::wholeWords($significant, $scales)]);
    }

    private static function digitAt(int $value, int $place): int
    {
        return intdiv($value, $place) % self::BASE;
    }

    /**
     * @param list<string> $words
     */
    private static function join(array $words, string $joiner): string
    {
        return implode(" {$joiner} ", array_values(array_filter($words, static fn(string $word): bool => $word !== '')));
    }

    // Malagasy reads upwards: units, then tens, then hundreds. The smaller part joins exactly
    // 100 with "amby" ("dimy amby zato") and 200–900 with "sy" ("dimy sy roanjato"); "iraika"
    // replaces "iray" only right before "ambin'ny" or "amby".
    private static function belowThousand(int $value): string
    {
        $units = self::digitAt($value, 1);
        $tens = self::digitAt($value, self::BASE);
        $hundreds = self::digitAt($value, self::HUNDRED);
        $isOneBeforeAmby = $units === 1 && ($tens > 0 || $hundreds === 1);
        $unitWord = $isOneBeforeAmby ? Data::UNIT_ONE_IN_COMPOUND : Data::UNIT_WORDS[$units];
        $joiner = $hundreds === 1 ? Data::JOINER_BELOW_THOUSAND : Data::JOINER_BETWEEN_GROUPS;

        return self::join([self::belowHundred($unitWord, $units, $tens), Data::HUNDRED_WORDS[$hundreds]], $joiner);
    }

    // 11–19 join with "ambin'ny"; other units join tens with "amby".
    private static function belowHundred(string $unitWord, int $units, int $tens): string
    {
        if ($tens === 1 && $units > 0) {
            return \sprintf('%s %s %s', $unitWord, Data::JOINER_UNITS_BEFORE_TEN, Data::TEN_WORDS[1]);
        }

        return self::join([$unitWord, Data::TEN_WORDS[$tens]], Data::JOINER_BELOW_THOUSAND);
    }

    /**
     * The digits a group counts, without leading zeros: '' when the group is absent. The
     * largest group takes every digit left, so its count can be any size.
     *
     * @param array{word: string, exponent: int, countDigits: int|null} $scale
     */
    private static function scaleCount(string $digits, array $scale): string
    {
        $end = \strlen($digits) - $scale['exponent'];
        if ($end <= 0) {
            return '';
        }
        $start = $scale['countDigits'] === null ? 0 : max(0, $end - $scale['countDigits']);

        return ltrim(substr($digits, $start, $end - $start), self::ZERO_DIGIT);
    }

    /**
     * A count of one is bare only for the first group: "arivo", but "iray alina", "iray
     * tapitrisa". Counts are read with every rule, so 1000 lavitrisa is "arivo lavitrisa".
     *
     * @param array{word: string, exponent: int, countDigits: int|null} $scale
     * @param non-empty-list<array{word: string, exponent: int, countDigits: int|null}> $scales
     */
    private static function scaleWords(string $digits, array $scale, array $scales): string
    {
        $count = self::scaleCount($digits, $scale);
        if ($count === '') {
            return '';
        }
        if ($count === self::ONE && $scale['exponent'] === $scales[0]['exponent']) {
            return $scale['word'];
        }

        return self::wholeWords($count, $scales) . ' ' . $scale['word'];
    }
}
