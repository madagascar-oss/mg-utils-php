<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Date\Internal;

use Mdgascar\MdgUtils\Date\DateStyle;
use Mdgascar\MdgUtils\Date\Generated\Data;

/**
 * Reads the fields of a date written in one style. Each CLDR pattern letter of the style
 * becomes a capture: names by their index, numbers as people type them (one or two digits for
 * day and month, up to four for the year).
 *
 * @internal not part of the public API; use `MalagasyDate::parse()`
 */
final class DateReader
{
    private const string PATTERN_TOKEN = '/EEEE|MMMM|MMM|MM|dd|d|y|\s+|[^\sEMdy]+/';
    private const string DAY_OR_MONTH = '([0-9]{1,2})';
    private const string YEAR = '([0-9]{1,4})';
    private const string ANY_SPACE = '[\s\p{Zs}]+';
    private const string WHITESPACE = '/^\s+$/';
    private const string FIELD_YEAR = 'year';
    private const string FIELD_MONTH = 'month';
    private const string FIELD_DAY = 'day';
    private const string FIELD_WEEKDAY = 'weekday';

    /**
     * The fields the text holds in this style, or null when it is not written in it.
     *
     * @return array{year: int, month: int, day: int, weekday: int|null}|null
     */
    public static function read(string $text, DateStyle $style): ?array
    {
        [$pattern, $tokens] = self::compile($style->pattern());
        if (preg_match($pattern, $text, $match) !== 1) {
            return null;
        }
        $fields = [self::FIELD_WEEKDAY => null];
        foreach ($tokens as $index => $token) {
            $fields[self::fieldOf($token)] = self::value($token, $match[$index + 1]);
        }

        return [
            'year' => $fields[self::FIELD_YEAR] ?? 0,
            'month' => $fields[self::FIELD_MONTH] ?? 0,
            'day' => $fields[self::FIELD_DAY] ?? 0,
            'weekday' => $fields[self::FIELD_WEEKDAY],
        ];
    }

    /**
     * @return array{string, list<string>} the pattern, and the letters in capture order
     */
    private static function compile(string $stylePattern): array
    {
        preg_match_all(self::PATTERN_TOKEN, $stylePattern, $parts);
        $tokens = [];
        $source = '';
        foreach ($parts[0] as $part) {
            $capture = self::capture($part);
            if ($capture === null) {
                $source .= preg_match(self::WHITESPACE, $part) === 1 ? self::ANY_SPACE : preg_quote($part, '/');
            } else {
                $tokens[] = $part;
                $source .= $capture;
            }
        }

        return ['/^' . $source . '$/iu', $tokens];
    }

    private static function capture(string $token): ?string
    {
        return match ($token) {
            'EEEE' => self::names(Data::WEEKDAY_NAMES),
            'MMMM' => self::names(Data::MONTH_NAMES),
            'MMM' => self::names(Data::MONTH_ABBREVIATIONS),
            'MM', 'dd', 'd' => self::DAY_OR_MONTH,
            'y' => self::YEAR,
            default => null,
        };
    }

    private static function fieldOf(string $token): string
    {
        return match ($token) {
            'EEEE' => self::FIELD_WEEKDAY,
            'MMMM', 'MMM', 'MM' => self::FIELD_MONTH,
            'dd', 'd' => self::FIELD_DAY,
            default => self::FIELD_YEAR,
        };
    }

    // The number a capture stands for: a name's index, or the digits.
    private static function value(string $token, string $text): int
    {
        $names = match ($token) {
            'EEEE' => Data::WEEKDAY_NAMES,
            'MMMM' => Data::MONTH_NAMES,
            'MMM' => Data::MONTH_ABBREVIATIONS,
            default => null,
        };
        if ($names === null) {
            return (int) $text;
        }
        $index = array_search(mb_strtolower($text), array_map(mb_strtolower(...), $names), true);

        return \is_int($index) ? $index : 0;
    }

    /**
     * @param list<string> $names index 0 is ''
     */
    private static function names(array $names): string
    {
        $quoted = array_map(static fn(string $name): string => preg_quote($name, '/'), array_filter($names, static fn(string $name): bool => $name !== ''));

        return '(' . implode('|', $quoted) . ')';
    }
}
