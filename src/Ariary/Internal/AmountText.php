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

    /** @var array{whitespace: string, grouped: string}|null */
    private static ?array $patterns = null;

    public static function read(string $text): int|MgUtilsException
    {
        $length = preg_match_all('/./su', $text);
        if ($length === false || $length > self::MAX_INPUT_LENGTH) {
            return new MgUtilsException(ErrorCode::InvalidFormat, \sprintf('Amounts are at most %d characters of valid text.', self::MAX_INPUT_LENGTH));
        }
        $digits = self::digits($text);
        if ($digits === null) {
            return new MgUtilsException(ErrorCode::InvalidFormat, 'Expected an amount such as Ar 1 500 (no commas, no decimals).');
        }
        if (\strlen($digits) > \strlen((string) Data::MAX_AMOUNT) || (int) $digits > Data::MAX_AMOUNT) {
            return new MgUtilsException(ErrorCode::OutOfRange, \sprintf('Amounts go up to %d.', Data::MAX_AMOUNT));
        }

        return (int) $digits;
    }

    private static function digits(string $text): ?string
    {
        $amount = self::stripCurrency(self::trim($text));
        if ($amount === null) {
            return null;
        }
        $patterns = self::patterns();
        $normalized = (string) preg_replace($patterns['whitespace'], self::SPACE, $amount);
        $isAmount = preg_match(self::PLAIN_AMOUNT, $normalized) === 1 || preg_match($patterns['grouped'], $normalized) === 1;

        return $isAmount ? (string) preg_replace(self::NON_DIGITS, '', $normalized) : null;
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
     * @return array{whitespace: string, grouped: string}
     */
    private static function patterns(): array
    {
        if (self::$patterns === null) {
            $isSpace = static fn(string $separator): bool => preg_match('/^[\s\p{Zs}]$/u', $separator) === 1;
            $spaces = array_filter(Data::READABLE_SEPARATORS, $isSpace);
            $others = array_filter(Data::READABLE_SEPARATORS, static fn(string $separator): bool => !$isSpace($separator));
            $quote = static fn(string $separator): string => preg_quote($separator, '/');
            self::$patterns = [
                'whitespace' => '/[' . implode('', array_map($quote, $spaces)) . ']/u',
                'grouped' => \sprintf('/^[1-9][0-9]{0,%1$d}([ %2$s])[0-9]{%3$d}(?:\1[0-9]{%3$d})*$/', Data::GROUP_SIZE - 1, implode('', array_map($quote, $others)), Data::GROUP_SIZE),
            ];
        }

        return self::$patterns;
    }
}
