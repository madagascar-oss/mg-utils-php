<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Ariary\Internal;

use Madagascar\MgUtils\Ariary\Generated\Data;
use Madagascar\MgUtils\ErrorCode;
use Madagascar\MgUtils\MgUtilsException;

/**
 * Reads amounts typed by users. Patterns are built once from the spec data.
 *
 * @internal not part of the public API; use `Ariary::parse()`, `tryParse()` or `safeParse()`
 */
final class AmountText
{
    // Bounded so a parser that reads user input never scans unbounded text.
    private const int MAX_INPUT_LENGTH = 32;
    private const string SPACE = ' ';
    private const string EDGE_SPACES = '/^[\s\p{Zs}]+|[\s\p{Zs}]+$/u';
    private const string PLAIN_AMOUNT = '/^(?:0|[1-9][0-9]*)$/';
    private const string NON_DIGITS = '/[^0-9]/';

    private const string DECIMALS = '/^[0-9]+$/';
    private const string IS_SPACE = '/^[\s\p{Zs}]$/u';
    private const string POINT = '.';
    // The whole part and, at most, the decimals.
    private const int MAX_PARTS = 2;

    /** @var array<string, array{whitespace: ?string, grouped: string}> */
    private static array $patterns = [];

    /**
     * Reads the text as digits, with a `.` before any decimals: `'1 500,50 Ar'` → `'1500.50'`.
     *
     * @param ?string          $grouping the grouping character, or null for every Malagasy one
     * @param non-empty-string $decimal  the decimal character
     */
    public static function read(string $text, ?string $grouping, string $decimal): string|MgUtilsException
    {
        $length = preg_match_all('/./su', $text);
        if ($length === false || $length > self::MAX_INPUT_LENGTH) {
            return new MgUtilsException(ErrorCode::InvalidFormat, \sprintf('Amounts are at most %d characters of valid text.', self::MAX_INPUT_LENGTH));
        }
        $amount = self::stripCurrency(self::trim($text));
        $digits = $amount === null ? null : self::digits($amount, self::patterns(self::groupingCharacters($grouping, $decimal)), $decimal);

        return $digits ?? new MgUtilsException(ErrorCode::InvalidFormat, 'Expected an amount such as Ar 1 500 or Ar 1 500,50.');
    }

    /**
     * @param array{whitespace: ?string, grouped: string} $patterns
     * @param non-empty-string                            $decimal
     */
    private static function digits(string $amount, array $patterns, string $decimal): ?string
    {
        $parts = explode($decimal, $amount);
        if (\count($parts) > self::MAX_PARTS) {
            return null;
        }
        $whole = self::wholeDigits($parts[0], $patterns);
        if ($whole === null || !isset($parts[1])) {
            return $whole;
        }

        return preg_match(self::DECIMALS, $parts[1]) === 1 ? $whole . self::POINT . $parts[1] : null;
    }

    /**
     * @param array{whitespace: ?string, grouped: string} $patterns
     */
    private static function wholeDigits(string $whole, array $patterns): ?string
    {
        $normalized = $patterns['whitespace'] === null ? $whole : (string) preg_replace($patterns['whitespace'], self::SPACE, $whole);
        $isAmount = preg_match(self::PLAIN_AMOUNT, $normalized) === 1 || preg_match($patterns['grouped'], $normalized) === 1;

        return $isAmount ? (string) preg_replace(self::NON_DIGITS, '', $normalized) : null;
    }

    /**
     * Every Malagasy separator (spaces and dot) when none is chosen; else the chosen one, a
     * space standing for every kind of space. The decimal character never counts as grouping.
     *
     * @return list<string>
     */
    private static function groupingCharacters(?string $grouping, string $decimal): array
    {
        if ($grouping === null) {
            return array_values(array_filter(Data::READABLE_SEPARATORS, static fn(string $separator): bool => $separator !== $decimal));
        }

        return self::isSpace($grouping) ? array_values(array_filter(Data::READABLE_SEPARATORS, self::isSpace(...))) : [$grouping];
    }

    private static function isSpace(string $character): bool
    {
        return preg_match(self::IS_SPACE, $character) === 1;
    }

    private static function trim(string $text): string
    {
        return (string) preg_replace(self::EDGE_SPACES, '', $text);
    }

    private static function stripCurrency(string $text): ?string
    {
        $lower = strtolower($text);
        $prefix = self::firstMatch(static fn(string $token): bool => str_starts_with($lower, $token));
        $suffix = self::firstMatch(static fn(string $token): bool => str_ends_with($lower, $token));
        if ($prefix !== '' && $suffix !== '') {
            return null;
        }

        return self::trim(substr($text, \strlen($prefix), \strlen($text) - \strlen($prefix) - \strlen($suffix)));
    }

    /**
     * @param \Closure(string): bool $matches
     */
    private static function firstMatch(\Closure $matches): string
    {
        foreach (Data::CURRENCY_TOKENS as $token) {
            if ($matches($token)) {
                return $token;
            }
        }

        return '';
    }

    /**
     * Built once per set of grouping characters. Spaces of every kind count as one separator:
     * they are turned into a plain space first.
     *
     * @param list<string> $grouping
     *
     * @return array{whitespace: ?string, grouped: string}
     */
    private static function patterns(array $grouping): array
    {
        $key = implode('', $grouping);
        if (!isset(self::$patterns[$key])) {
            $spaces = array_filter($grouping, self::isSpace(...));
            $others = array_filter($grouping, static fn(string $separator): bool => !self::isSpace($separator));
            $quote = static fn(string $separator): string => preg_quote($separator, '/');
            $class = ($spaces === [] ? '' : self::SPACE) . implode('', array_map($quote, $others));
            self::$patterns[$key] = [
                'whitespace' => $spaces === [] ? null : '/[' . implode('', array_map($quote, $spaces)) . ']/u',
                'grouped' => \sprintf('/^[1-9][0-9]{0,%1$d}([%2$s])[0-9]{%3$d}(?:\1[0-9]{%3$d})*$/', Data::GROUP_SIZE - 1, $class, Data::GROUP_SIZE),
            ];
        }

        return self::$patterns[$key];
    }
}
