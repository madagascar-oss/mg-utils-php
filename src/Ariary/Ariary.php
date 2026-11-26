<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Ariary;

use Madagascar\MgUtils\Ariary\Generated\Data;
use Madagascar\MgUtils\Ariary\Internal\AmountText;
use Madagascar\MgUtils\ErrorCode;
use Madagascar\MgUtils\Internal\DecimalText;
use Madagascar\MgUtils\MgUtilsException;
use Madagascar\MgUtils\SafeParseResult;
use Madagascar\MgUtils\Words\NumberWords;

/**
 * An amount in Malagasy Ariary (MGA), whole or decimal.
 *
 * Example:
 *
 *     Ariary::fromInt(1500)->format();                   // 'Ar 1 500'
 *     Ariary::fromFloat(1500.5)->format();               // 'Ar 1 500,5'
 *     Ariary::fromInt(1500)->format(Grouping::Dot);      // 'Ar 1.500'
 *     Ariary::parse('1 500,50 Ar')->amount;              // 1500.5
 *     Ariary::fromInt(1500)->toWords();                  // 'dimanjato sy arivo ariary'
 *     Ariary::fromFmg(7501)->amount;                     // 1500.2
 */
final readonly class Ariary
{
    private const string POINT = '.';

    /**
     * @param int|float $amount an int when whole, a float when it has decimals
     */
    private function __construct(
        public int|float $amount,
    ) {}

    /**
     * A whole amount, from 0 to PHP_INT_MAX (the spec sets no maximum).
     *
     * Example: `Ariary::fromInt(1500)->amount` is `1500`.
     *
     * @throws MgUtilsException with `ErrorCode::OutOfRange` below 0
     */
    public static function fromInt(int $amount): self
    {
        return new self(self::checked($amount));
    }

    /**
     * An amount with decimals, from 0 to 9 007 199 254 740 991. A whole value is kept as an int.
     *
     * Example: `Ariary::fromFloat(1500.5)->format()` returns `'Ar 1 500,5'`.
     *
     * @throws MgUtilsException with `ErrorCode::InvalidType` for NAN or INF,
     *                          `ErrorCode::OutOfRange` below 0 or above 9 007 199 254 740 991
     */
    public static function fromFloat(float $amount): self
    {
        return new self(self::checked($amount));
    }

    /**
     * Reads an amount such as `Ar 1 500`, `1.500 Ar`, `MGA 1500` or `1 500,50`. Without
     * `$grouping`, every Malagasy separator (spaces or dot) is read; the decimal separator is a
     * comma by default. For text in another convention, say so:
     * `Ariary::parse('1,500.50', Grouping::Comma, DecimalSeparator::Dot)`.
     *
     * Example: `Ariary::parse('1 500,50 Ar')->amount` is `1500.5`.
     *
     * @throws MgUtilsException with `ErrorCode::InvalidFormat` (signs, a dot as decimal point by
     *                          default), `ErrorCode::OutOfRange`, or `ErrorCode::InvalidType`
     *                          when `$grouping` and `$decimal` are the same character
     */
    public static function parse(string $text, ?Grouping $grouping = null, DecimalSeparator $decimal = DecimalSeparator::DEFAULT): self
    {
        $amount = self::read($text, $grouping, $decimal);
        if ($amount instanceof MgUtilsException) {
            throw $amount;
        }

        return $amount;
    }

    /**
     * Like `parse()`, but returns `null` instead of throwing.
     *
     * Example: `Ariary::tryParse('1.5')` returns `null` (a dot is not a decimal point by default).
     */
    public static function tryParse(mixed $text, ?Grouping $grouping = null, DecimalSeparator $decimal = DecimalSeparator::DEFAULT): ?self
    {
        return self::safeParse($text, $grouping, $decimal)->data;
    }

    /**
     * Like `parse()`, but never throws: returns the amount or the reason it was rejected.
     *
     * Example: `Ariary::safeParse('1.5')->error?->errorCode` is `ErrorCode::InvalidFormat`.
     *
     * @return SafeParseResult<self>
     */
    public static function safeParse(mixed $text, ?Grouping $grouping = null, DecimalSeparator $decimal = DecimalSeparator::DEFAULT): SafeParseResult
    {
        if (!\is_string($text)) {
            return SafeParseResult::failure(new MgUtilsException(ErrorCode::InvalidType, 'Expected the amount as a string.'));
        }
        $amount = self::read($text, $grouping, $decimal);

        return $amount instanceof MgUtilsException ? SafeParseResult::failure($amount) : SafeParseResult::success($amount);
    }

    /**
     * Converts old Malagasy francs (FMG), whole or decimal: 5 FMG = 1 Ar, exactly in decimal.
     *
     * Example: `Ariary::fromFmg(7501)->amount` is `1500.2`.
     *
     * @throws MgUtilsException with `ErrorCode::OutOfRange` below 0 or when too large,
     *                          `ErrorCode::InvalidType` for NAN or INF
     */
    public static function fromFmg(int|float $francs): self
    {
        return new self(self::fromDecimal(DecimalText::divide(self::checked($francs), Data::FRANC_DIVISION_FACTOR)));
    }

    /**
     * Writes an amount in old francs with the same layout as `format()`.
     *
     * Example: `Ariary::formatFmg(7500)` returns `'Fmg 7 500'`.
     *
     * @throws MgUtilsException with `ErrorCode::OutOfRange`, or `ErrorCode::InvalidType` for NAN,
     *                          INF, or the same character for grouping and decimal
     */
    public static function formatFmg(int|float $francs, Grouping $grouping = Grouping::DEFAULT, SymbolPosition $position = SymbolPosition::Before, DecimalSeparator $decimal = DecimalSeparator::DEFAULT): string
    {
        return self::layOut(self::checked($francs), Data::FRANC_SYMBOL, $position, self::separators($grouping, $decimal));
    }

    /**
     * Writes the amount the Malagasy way: `Ar 1 500,5`, with no-break spaces between digit
     * groups and a comma before the decimals. Other conventions are opt-in.
     *
     * Example: `Ariary::fromInt(1500)->format(Grouping::Dot, SymbolPosition::After)` returns `'1.500 Ar'`.
     *
     * @throws MgUtilsException with `ErrorCode::InvalidType` when `$grouping` and `$decimal` are the same character
     */
    public function format(Grouping $grouping = Grouping::DEFAULT, SymbolPosition $position = SymbolPosition::Before, DecimalSeparator $decimal = DecimalSeparator::DEFAULT): string
    {
        return self::layOut($this->amount, Data::CURRENCY_SYMBOL, $position, self::separators($grouping, $decimal));
    }

    /**
     * The amount in Malagasy words, followed by `ariary`; decimals follow `faingo`.
     *
     * Example: `Ariary::fromFloat(1500.5)->toWords()` returns `'dimanjato sy arivo faingo dimy ariary'`.
     */
    public function toWords(): string
    {
        $words = \is_int($this->amount) ? NumberWords::fromInt($this->amount) : NumberWords::fromFloat($this->amount);

        return $words . ' ' . Data::CURRENCY_WORD;
    }

    /**
     * The amount in old Malagasy francs: 1 Ar = 5 FMG, exactly in decimal.
     *
     * Example: `Ariary::fromFloat(0.07)->toFmg()` returns `0.35` (never `0.35000000000000003`).
     *
     * @throws MgUtilsException with `ErrorCode::OutOfRange` when the francs would be too large to hold exactly
     */
    public function toFmg(): int|float
    {
        return self::fromDecimal(DecimalText::multiply($this->amount, Data::FRANCS_PER_ARIARY));
    }

    private static function read(string $text, ?Grouping $grouping, DecimalSeparator $decimal): self|MgUtilsException
    {
        if ($grouping?->separator() === $decimal->separator()) {
            return self::sameSeparators();
        }
        $digits = AmountText::read($text, $grouping?->separator(), $decimal->separator());
        if ($digits instanceof MgUtilsException) {
            return $digits;
        }
        $amount = DecimalText::toNumber($digits);

        return $amount === null ? self::outOfRange() : new self($amount);
    }

    private static function fromDecimal(string $text): int|float
    {
        return DecimalText::toNumber($text) ?? throw self::outOfRange();
    }

    // An int stays an int (it stops at PHP_INT_MAX by itself); a float must be finite and exact,
    // and becomes an int when whole.
    private static function checked(int|float $value): int|float
    {
        if (\is_float($value) && !is_finite($value)) {
            throw new MgUtilsException(ErrorCode::InvalidType, 'Amounts are finite numbers.');
        }
        if ($value < Data::MIN_AMOUNT) {
            throw new MgUtilsException(ErrorCode::OutOfRange, \sprintf('Amounts start at %d.', Data::MIN_AMOUNT));
        }

        return \is_int($value) ? $value : self::fromDecimal(DecimalText::plain($value));
    }

    private static function outOfRange(): MgUtilsException
    {
        return new MgUtilsException(ErrorCode::OutOfRange, \sprintf('Amounts go up to %d, or %d with decimals.', PHP_INT_MAX, DecimalText::MAX_EXACT_FLOAT_INTEGER));
    }

    private static function sameSeparators(): MgUtilsException
    {
        return new MgUtilsException(ErrorCode::InvalidType, 'Grouping and decimal separators must be different characters.');
    }

    /**
     * @return array{grouping: string, decimal: string} the two characters, checked to differ
     */
    private static function separators(Grouping $grouping, DecimalSeparator $decimal): array
    {
        if ($grouping->separator() === $decimal->separator()) {
            throw self::sameSeparators();
        }

        return ['grouping' => $grouping->separator(), 'decimal' => $decimal->separator()];
    }

    /**
     * @param array{grouping: string, decimal: string} $separators
     */
    private static function layOut(int|float $value, string $symbol, SymbolPosition $position, array $separators): string
    {
        $parts = explode(self::POINT, DecimalText::plain($value));
        // Split from the right: reverse, cut into groups, then restore each group and their order.
        $groups = array_map(strrev(...), array_reverse(str_split(strrev($parts[0]), Data::GROUP_SIZE)));
        $digits = implode($separators['grouping'], $groups);
        if (isset($parts[1])) {
            $digits .= $separators['decimal'] . $parts[1];
        }

        return match ($position) {
            SymbolPosition::Before => $symbol . Data::SYMBOL_SEPARATOR . $digits,
            SymbolPosition::After => $digits . Data::SYMBOL_SEPARATOR . $symbol,
        };
    }
}
