<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Words;

use Madagascar\MgUtils\ErrorCode;
use Madagascar\MgUtils\MgUtilsException;
use Madagascar\MgUtils\Words\Generated\Data;

/**
 * Malagasy number words.
 */
final class NumberWords
{
    private const int BASE = 10;
    private const int HUNDRED = self::BASE * self::BASE;

    /**
     * Writes a whole number in Malagasy words, smallest part first.
     *
     * Example: `NumberWords::fromInt(2026)` returns `'enina amby roapolo sy roa arivo'`.
     *
     * @throws MgUtilsException with `ErrorCode::OutOfRange` below 0 or above 999 999 999 999
     */
    public static function fromInt(int $value): string
    {
        if ($value < Data::MIN_VALUE || $value > Data::MAX_VALUE) {
            throw new MgUtilsException(
                ErrorCode::OutOfRange,
                \sprintf('NumberWords supports %d to %d.', Data::MIN_VALUE, Data::MAX_VALUE),
            );
        }
        if ($value === 0) {
            return Data::ZERO_WORD;
        }
        $groups = [self::belowThousand($value % Data::SCALES[0]['value'])];
        foreach (Data::SCALES as $scale) {
            $groups[] = self::scaleWords($value, $scale);
        }

        return self::join($groups, Data::JOINER_BETWEEN_GROUPS);
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

    // Malagasy reads upwards: units, then tens, then hundreds. "iray" becomes "iraika" when
    // something follows it, and only 11–19 use "ambin' ny" (https://malagasyword.org/bins/teny2/amby).
    private static function belowThousand(int $value): string
    {
        $units = self::digitAt($value, 1);
        $tens = self::digitAt($value, self::BASE);
        $unitWord = $units === 1 && $value > 1 ? Data::UNIT_ONE_IN_COMPOUND : Data::UNIT_WORDS[$units];
        $lowerWords = $tens === 1 && $units > 0
            ? [\sprintf('%s %s %s', $unitWord, Data::JOINER_UNITS_BEFORE_TEN, Data::TEN_WORDS[1])]
            : [$unitWord, Data::TEN_WORDS[$tens]];
        $lowerWords[] = Data::HUNDRED_WORDS[self::digitAt($value, self::HUNDRED)];

        return self::join($lowerWords, Data::JOINER_BELOW_THOUSAND);
    }

    /**
     * A count of one is the bare group word: "arivo", not "iray arivo".
     *
     * @param array{value: int, word: string, maxCount: int} $scale
     */
    private static function scaleWords(int $value, array $scale): string
    {
        $count = intdiv($value, $scale['value']) % ($scale['maxCount'] + 1);

        return match ($count) {
            0 => '',
            1 => $scale['word'],
            default => self::belowThousand($count) . ' ' . $scale['word'],
        };
    }
}
