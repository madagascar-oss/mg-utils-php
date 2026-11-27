<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Words\Internal;

use Mdgascar\MdgUtils\Words\Generated\Data;
use Mdgascar\MdgUtils\Words\NumberSystem;

/**
 * Whole numbers in words, from their decimal digits, so counts of any length stay exact.
 *
 * @internal not part of the public API; use `NumberWords`
 */
final class Cardinal
{
    private const int BASE = 10;
    private const int HUNDRED = self::BASE * self::BASE;
    private const int BELOW_THOUSAND_DIGITS = 3;
    private const string ONE = '1';
    private const string ZERO_DIGIT = '0';

    /**
     * A whole number in words, groups joined by "sy", smallest first:
     * `'2586'` → `'enina amby valopolo sy dimanjato sy roa arivo'`.
     */
    public static function whole(string $digits, NumberSystem $system): string
    {
        $significant = ltrim($digits, self::ZERO_DIGIT);
        if ($significant === '') {
            return Data::ZERO_WORD;
        }

        return self::number($significant, self::scalesFor($significant, $system), Role::Number);
    }

    /**
     * A count as it is written before a group word, every part joined by "amby" (rule 6):
     * `'25000'` → `'dimy arivo amby roa alina'`.
     */
    public static function count(string $count, NumberSystem $system): string
    {
        $scales = $system === NumberSystem::Traditional ? Data::TRADITIONAL_SCALES : Data::SCALES;

        return self::number($count, $scales, Role::Count);
    }

    /**
     * The whole digits, then "faingo" and the decimals when there are any. Each leading zero
     * after the point is "aotra"; the rest reads as a whole number.
     */
    public static function parts(string $whole, ?string $decimals, NumberSystem $system): string
    {
        $words = self::whole($whole, $system);
        if ($decimals === null) {
            return $words;
        }
        $significant = ltrim($decimals, self::ZERO_DIGIT);
        $zeros = array_fill(0, \strlen($decimals) - \strlen($significant), Data::ZERO_WORD);

        return \sprintf('%s %s %s', $words, Data::DECIMAL_SEPARATOR, implode(' ', [...$zeros, self::whole($significant, $system)]));
    }

    /**
     * Rule 12: a number longer than the traditional names reach is read with the standard scales.
     *
     * @return non-empty-list<array{word: string, exponent: int, countDigits: int|null}>
     */
    private static function scalesFor(string $digits, NumberSystem $system): array
    {
        if ($system === NumberSystem::Standard) {
            return Data::SCALES;
        }
        $largest = Data::TRADITIONAL_SCALES[\count(Data::TRADITIONAL_SCALES) - 1];
        $reach = $largest['exponent'] + $largest['countDigits'];

        return \strlen($digits) > $reach ? Data::SCALES : Data::TRADITIONAL_SCALES;
    }

    /**
     * @param non-empty-list<array{word: string, exponent: int, countDigits: int|null}> $scales
     */
    private static function number(string $significant, array $scales, Role $role): string
    {
        $upper = [];
        foreach ($scales as $scale) {
            $upper[] = self::scaleWords($significant, $scale, $scales);
        }
        $upper = array_values(array_filter($upper, static fn(string $word): bool => $word !== ''));
        $joiner = $role === Role::Count ? Data::JOINER_BELOW_THOUSAND : Data::JOINER_BETWEEN_GROUPS;
        $lowest = self::belowThousand((int) substr($significant, -self::BELOW_THOUSAND_DIGITS), $role, $upper === [] ? '' : $joiner);

        return self::join([$lowest, ...$upper], $joiner);
    }

    /**
     * Rule 4: the part below 100 joins exactly 100 with "amby" and 200–900 with "sy"; inside a
     * count every join is "amby". "iraika" replaces "iray" right before "amby" or "ambin'ny".
     */
    private static function belowThousand(int $value, Role $role, string $nextJoiner): string
    {
        $units = self::digitAt($value, 1);
        $tens = self::digitAt($value, self::BASE);
        $hundreds = self::digitAt($value, self::HUNDRED);
        $joiner = $role === Role::Count || $hundreds === 1 ? Data::JOINER_BELOW_THOUSAND : Data::JOINER_BETWEEN_GROUPS;
        $afterUnits = $tens > 0 || $hundreds > 0 ? $joiner : $nextJoiner;
        $isOneBeforeAmby = $units === 1 && ($tens > 0 || $afterUnits === Data::JOINER_BELOW_THOUSAND);
        $unitWord = $isOneBeforeAmby ? Data::UNIT_ONE_IN_COMPOUND : Data::UNIT_WORDS[$units];

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
     * A group's count and word, '' when the group is absent. A count of one is bare only for
     * arivo (rule 7).
     *
     * @param array{word: string, exponent: int, countDigits: int|null}                   $scale
     * @param non-empty-list<array{word: string, exponent: int, countDigits: int|null}> $scales
     */
    private static function scaleWords(string $digits, array $scale, array $scales): string
    {
        $end = \strlen($digits) - $scale['exponent'];
        if ($end <= 0) {
            return '';
        }
        $start = $scale['countDigits'] === null ? 0 : max(0, $end - $scale['countDigits']);
        $count = ltrim(substr($digits, $start, $end - $start), self::ZERO_DIGIT);
        if ($count === '') {
            return '';
        }
        if ($count === self::ONE && $scale['exponent'] === Data::SCALES[0]['exponent']) {
            return $scale['word'];
        }

        return self::number($count, $scales, Role::Count) . ' ' . $scale['word'];
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
}
