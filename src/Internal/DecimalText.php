<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Internal;

/**
 * Exact decimal helpers. Amounts are int or float values, but words and money must never show
 * binary rounding artefacts (0.07 × 5 is 0.35000000000000003 in floating point), so these work
 * on the decimal digits of a value's shortest exact form.
 *
 * @internal not part of the public API
 */
final class DecimalText
{
    /** Largest integer a float holds exactly next to its neighbours (JavaScript's MAX_SAFE_INTEGER). */
    public const int MAX_EXACT_FLOAT_INTEGER = 9_007_199_254_740_991;

    private const int BASE = 10;
    private const string ZERO = '0';
    private const string POINT = '.';
    // A float round-trips with at most 17 significant digits, so 16 after the first.
    private const int MAX_FRACTION_PRECISION = 16;
    // sprintf('%.Ne') writes digits, then 'e' and a signed exponent: '1.5e+0', '8e-3'.
    private const string EXPONENT_MARK = 'e';

    /**
     * The shortest decimal form that reads back as the same value, never in exponent notation:
     * `1500.5` → `'1500.5'`, `1e-7` → `'0.0000001'`. It does not depend on the precision or
     * serialize_precision ini settings.
     */
    public static function plain(int|float $value): string
    {
        if (\is_int($value)) {
            return (string) $value;
        }
        $scientific = '';
        for ($precision = 0; $precision <= self::MAX_FRACTION_PRECISION; ++$precision) {
            $scientific = \sprintf("%.{$precision}e", $value);
            if ((float) $scientific === $value) {
                break;
            }
        }
        $parts = explode(self::EXPONENT_MARK, $scientific);
        $digits = str_replace(self::POINT, '', $parts[0]);
        // d.ddd × 10^exponent: the digits after the first, less the exponent, follow the point.
        $places = \strlen($digits) - 1 - (int) ($parts[1] ?? 0);

        return self::fromScaled($digits, $places);
    }

    /**
     * `value × factor`, exactly, as decimal text: `multiply(0.07, 5)` → `'0.35'`.
     */
    public static function multiply(int|float $value, int $factor): string
    {
        [$digits, $places] = self::scaled($value);

        return self::fromScaled(self::multiplyDigits($digits, $factor), $places);
    }

    /**
     * `value ÷ divisor` for a divisor of 10, exactly, as decimal text: multiplies by
     * `10 ÷ divisor` (the division factor), then moves the point one place left.
     * `divide(7501, 2)` → `'1500.2'` (7501 ÷ 5).
     */
    public static function divide(int|float $value, int $divisionFactor): string
    {
        [$digits, $places] = self::scaled($value);

        return self::fromScaled(self::multiplyDigits($digits, $divisionFactor), $places + 1);
    }

    /**
     * Decimal text as a value: an int when it is whole, else a float. Null when the value is
     * too large to hold exactly (above PHP_INT_MAX, or above 9 007 199 254 740 991 with decimals).
     */
    public static function toNumber(string $text): int|float|null
    {
        $parts = explode(self::POINT, $text);
        $whole = ltrim($parts[0], self::ZERO);
        $decimals = rtrim($parts[1] ?? '', self::ZERO);
        if ($decimals === '') {
            return self::exceeds($whole, (string) PHP_INT_MAX) ? null : (int) $whole;
        }
        $value = (float) $text;

        return $value > self::MAX_EXACT_FLOAT_INTEGER ? null : $value;
    }

    /**
     * Whether digit text without leading zeros is larger than `$max`, compared as text because
     * (int) would silently clamp a longer number to PHP_INT_MAX.
     */
    public static function exceeds(string $digits, string $max): bool
    {
        return \strlen($digits) > \strlen($max) || (\strlen($digits) === \strlen($max) && strcmp($digits, $max) > 0);
    }

    /**
     * @return array{string, int} all the digits as one integer, and how many come after the point
     */
    private static function scaled(int|float $value): array
    {
        $parts = explode(self::POINT, self::plain($value));
        $decimals = $parts[1] ?? '';

        return [$parts[0] . $decimals, \strlen($decimals)];
    }

    // Schoolbook multiplication of digit text by a small whole number.
    private static function multiplyDigits(string $digits, int $factor): string
    {
        $result = '';
        $carry = 0;
        for ($index = \strlen($digits) - 1; $index >= 0; --$index) {
            $product = (int) $digits[$index] * $factor + $carry;
            $result = (string) ($product % self::BASE) . $result;
            $carry = intdiv($product, self::BASE);
        }

        return ($carry > 0 ? (string) $carry : '') . $result;
    }

    // Digit text with `$places` of them after the point, normalised: no leading zeros before the
    // point, no trailing zeros after it, and no point when nothing follows it.
    private static function fromScaled(string $digits, int $places): string
    {
        if ($places <= 0) {
            $whole = ltrim($digits . str_repeat(self::ZERO, -$places), self::ZERO);

            return $whole === '' ? self::ZERO : $whole;
        }
        $padded = str_pad($digits, $places + 1, self::ZERO, \STR_PAD_LEFT);
        $point = \strlen($padded) - $places;
        $whole = ltrim(substr($padded, 0, $point), self::ZERO);
        $decimals = rtrim(substr($padded, $point), self::ZERO);
        $whole = $whole === '' ? self::ZERO : $whole;

        return $decimals === '' ? $whole : $whole . self::POINT . $decimals;
    }
}
