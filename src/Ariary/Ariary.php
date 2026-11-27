<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Ariary;

use Mdgascar\MdgUtils\Ariary\Generated\Data;
use Mdgascar\MdgUtils\Ariary\Internal\AmountText;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\Internal\DecimalText;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\SafeParseResult;
use Mdgascar\MdgUtils\Words\Internal\Cardinal;
use Mdgascar\MdgUtils\Words\NumberSystem;
use Mdgascar\MdgUtils\Words\NumberWords;

/**
 * An amount in Malagasy Ariary (MGA), whole or decimal.
 *
 * Example:
 *
 *     Ariary::fromInt(1500)->format();                   // 'Ar 1 500'
 *     Ariary::fromFloat(1500.5)->format();               // 'Ar 1 500,5'
 *     Ariary::fromInt(1500)->format(Grouping::Dot);      // 'Ar 1.500'
 *     Ariary::parse('1 500,50 Ar')->amount();            // 1500.5
 *     Ariary::fromInt(1500)->spell();                    // 'dimanjato sy arivo ariary'
 *     Ariary::fromFmg(7501)->amount();                   // 1500.2
 *
 * The amount is kept as its exact decimal `digits`, so nothing ever rounds: `amount()` gives an
 * int or float only when it holds every digit.
 */
final readonly class Ariary
{
    private const string POINT = '.';
    // Spec words rule 13: ASCII digits, then optionally a point and more digits.
    private const string DECIMAL_TEXT = '/^[0-9]+(?:\.[0-9]+)?$/D';

    /**
     * @param string $digits canonical decimal text: `'1500.5'`
     */
    private function __construct(
        public string $digits,
    ) {}

    /**
     * A whole amount, from 0 to PHP_INT_MAX (the spec sets no maximum).
     *
     * Example: `Ariary::fromInt(1500)->amount()` is `1500`.
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` below 0
     */
    public static function fromInt(int $amount): self
    {
        return new self(self::checked($amount));
    }

    /**
     * An amount given as plain decimal text, read exactly and with no upper limit, at most 100
     * characters (spec words rule 13).
     *
     * Example: `Ariary::fromText('1500.25')->format()` returns `'Ar 1 500,25'`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidFormat` for text that is not plain decimal
     */
    public static function fromText(string $amount): self
    {
        return new self(self::checkedText($amount));
    }

    /**
     * The amount as an int (whole) or float (with decimals).
     *
     * Example: `Ariary::parse('1 500,50 Ar')->amount()` returns `1500.5`.
     *
     * @throws MdgUtilsException with `ErrorCode::PrecisionLoss` when neither an int nor a float
     *                          holds every digit; read `digits` instead
     */
    public function amount(): int|float
    {
        return self::exactNumber($this->digits);
    }

    /**
     * An amount with decimals, from 0 to 9 007 199 254 740 991. A whole value is kept as an int.
     *
     * Example: `Ariary::fromFloat(1500.5)->format()` returns `'Ar 1 500,5'`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidType` for NAN or INF,
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
     * Example: `Ariary::parse('1 500,50 Ar')->amount()` is `1500.5`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidFormat` (signs, a dot as decimal point by
     *                          default), or `ErrorCode::InvalidType` when `$grouping` and
     *                          `$decimal` are the same character
     */
    public static function parse(string $text, ?Grouping $grouping = null, DecimalSeparator $decimal = DecimalSeparator::DEFAULT): self
    {
        $amount = self::read($text, $grouping, $decimal);
        if ($amount instanceof MdgUtilsException) {
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
            return SafeParseResult::failure(new MdgUtilsException(ErrorCode::InvalidType, 'Expected the amount as a string.'));
        }
        $amount = self::read($text, $grouping, $decimal);

        return $amount instanceof MdgUtilsException ? SafeParseResult::failure($amount) : SafeParseResult::success($amount);
    }

    /**
     * Converts old Malagasy francs (FMG), whole or decimal: 5 FMG = 1 Ar, exactly in decimal.
     *
     * Example: `Ariary::fromFmg(7501)->amount()` is `1500.2`.
     *
     * @param int|float|string $francs a number, or plain decimal text read exactly
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` below 0, `ErrorCode::InvalidType`
     *                          for NAN or INF, `ErrorCode::InvalidFormat` for text that is not
     *                          plain decimal
     */
    public static function fromFmg(int|float|string $francs): self
    {
        return new self(DecimalText::divide(self::anyDigits($francs), Data::FRANC_DIVISION_FACTOR));
    }

    /**
     * Writes an amount in old francs with the same layout as `format()`.
     *
     * Example: `Ariary::formatFmg(7500)` returns `'Fmg 7 500'`.
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange`, or `ErrorCode::InvalidType` for NAN,
     *                          INF, or the same character for grouping and decimal
     */
    public static function formatFmg(int|float|string $francs, Grouping $grouping = Grouping::DEFAULT, SymbolPosition $position = SymbolPosition::Before, DecimalSeparator $decimal = DecimalSeparator::DEFAULT): string
    {
        return self::layOut(self::anyDigits($francs), Data::FRANC_SYMBOL, $position, self::separators($grouping, $decimal));
    }

    /**
     * Writes an amount in old francs with the ISO code `MGF` instead of the symbol `Fmg`.
     *
     * Example: `Ariary::formatFmgCode(7500)` returns `'MGF 7 500'`.
     *
     * @throws MdgUtilsException as `formatFmg()`
     */
    public static function formatFmgCode(int|float|string $francs, Grouping $grouping = Grouping::DEFAULT, SymbolPosition $position = SymbolPosition::Before, DecimalSeparator $decimal = DecimalSeparator::DEFAULT): string
    {
        return self::layOut(self::anyDigits($francs), Data::FRANC_CODE, $position, self::separators($grouping, $decimal));
    }

    /**
     * Writes the amount the Malagasy way: `Ar 1 500,5`, with no-break spaces between digit
     * groups and a comma before the decimals. Other conventions are opt-in.
     *
     * Example: `Ariary::fromInt(1500)->format(Grouping::Dot, SymbolPosition::After)` returns `'1.500 Ar'`;
     * `Ariary::fromInt(1500)->format(currency: CurrencyDisplay::Code)` returns `'MGA 1 500'`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidType` when `$grouping` and `$decimal` are the same character
     */
    public function format(Grouping $grouping = Grouping::DEFAULT, SymbolPosition $position = SymbolPosition::Before, DecimalSeparator $decimal = DecimalSeparator::DEFAULT, CurrencyDisplay $currency = CurrencyDisplay::Symbol): string
    {
        $symbol = $currency === CurrencyDisplay::Code ? Data::CURRENCY_CODE : Data::CURRENCY_SYMBOL;

        return self::layOut($this->digits, $symbol, $position, self::separators($grouping, $decimal));
    }

    /**
     * The amount in Malagasy words, followed by `ariary`; decimals follow `faingo`.
     *
     * Example: `Ariary::fromFloat(1500.5)->spell()` returns `'dimanjato sy arivo faingo dimy ariary'`;
     * `Ariary::fromInt(30_000_000)->spell(NumberSystem::Traditional)` returns `'telo safatsiroa ariary'`.
     */
    public function spell(NumberSystem $system = NumberSystem::Standard): string
    {
        $parts = explode(self::POINT, $this->digits);

        return Cardinal::parts($parts[0], $parts[1] ?? null, $system) . ' ' . Data::CURRENCY_WORD;
    }

    /**
     * An amount given as plain decimal text, in Malagasy words followed by `ariary`, every digit
     * exact: for amounts too long for an int or float, such as on legal documents. Formatted
     * text (`'Ar 1 500'`) is read by `parse()` instead.
     *
     * Example: `Ariary::spellText('2000000000000000.9')` returns
     * `'roa tapitrisa lavitrisa faingo sivy ariary'`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidFormat` for text that is not plain decimal
     */
    public static function spellText(string $amount, NumberSystem $system = NumberSystem::Standard): string
    {
        return NumberWords::spell($amount, $system) . ' ' . Data::CURRENCY_WORD;
    }

    /**
     * The amount in old Malagasy francs: 1 Ar = 5 FMG, exactly in decimal.
     *
     * Example: `Ariary::fromFloat(0.07)->toFmg()` returns `0.35` (never `0.35000000000000003`).
     *
     * @throws MdgUtilsException with `ErrorCode::PrecisionLoss` when neither an int nor a float
     *                          holds the francs exactly; use `toFmgDigits()` instead
     */
    public function toFmg(): int|float
    {
        return self::exactNumber($this->toFmgDigits());
    }

    /**
     * The amount in old Malagasy francs as exact decimal text, of any size.
     *
     * Example: `Ariary::fromText('1500.07')->toFmgDigits()` returns `'7500.35'`.
     */
    public function toFmgDigits(): string
    {
        return DecimalText::multiply($this->digits, Data::FRANCS_PER_ARIARY);
    }

    private static function read(string $text, ?Grouping $grouping, DecimalSeparator $decimal): self|MdgUtilsException
    {
        if ($grouping?->separator() === $decimal->separator()) {
            return self::sameSeparators();
        }
        $digits = AmountText::read($text, $grouping?->separator(), $decimal->separator());

        return $digits instanceof MdgUtilsException ? $digits : new self(DecimalText::canonical($digits));
    }

    private static function exactNumber(string $digits): int|float
    {
        return DecimalText::toNumber($digits) ?? throw new MdgUtilsException(
            ErrorCode::PrecisionLoss,
            \sprintf('%s does not fit an int or float exactly; read the exact digits instead.', $digits),
        );
    }

    private static function anyDigits(int|float|string $value): string
    {
        return \is_string($value) ? self::checkedText($value) : self::checked($value);
    }

    private static function checkedText(string $text): string
    {
        if (\strlen($text) > Data::MAX_TEXT_LENGTH || preg_match(self::DECIMAL_TEXT, $text) !== 1) {
            throw new MdgUtilsException(
                ErrorCode::InvalidFormat,
                \sprintf('Amount text is digits with an optional .decimals, at most %d characters.', Data::MAX_TEXT_LENGTH),
            );
        }

        return DecimalText::canonical($text);
    }

    // A number's exact digits: an int as it is, a float finite and within its exact range.
    private static function checked(int|float $value): string
    {
        if (\is_float($value) && !is_finite($value)) {
            throw new MdgUtilsException(ErrorCode::InvalidType, 'Amounts are finite numbers.');
        }
        if ($value < Data::MIN_AMOUNT || (\is_float($value) && $value > DecimalText::MAX_EXACT_FLOAT_INTEGER)) {
            throw new MdgUtilsException(
                ErrorCode::OutOfRange,
                \sprintf('Amounts go from %d to %d, or to %d with decimals.', Data::MIN_AMOUNT, PHP_INT_MAX, DecimalText::MAX_EXACT_FLOAT_INTEGER),
            );
        }

        return DecimalText::plain($value);
    }

    private static function sameSeparators(): MdgUtilsException
    {
        return new MdgUtilsException(ErrorCode::InvalidType, 'Grouping and decimal separators must be different characters.');
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
    private static function layOut(string $digits, string $symbol, SymbolPosition $position, array $separators): string
    {
        $parts = explode(self::POINT, $digits);
        // Split from the right: reverse, cut into groups, then restore each group and their order.
        $groups = array_map(strrev(...), array_reverse(str_split(strrev($parts[0]), Data::GROUP_SIZE)));
        $text = implode($separators['grouping'], $groups);
        if (isset($parts[1])) {
            $text .= $separators['decimal'] . $parts[1];
        }

        return match ($position) {
            SymbolPosition::Before => $symbol . Data::SYMBOL_SEPARATOR . $text,
            SymbolPosition::After => $text . Data::SYMBOL_SEPARATOR . $symbol,
        };
    }
}
