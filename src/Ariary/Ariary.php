<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Ariary;

use Madagascar\MgUtils\Ariary\Generated\Data;
use Madagascar\MgUtils\Ariary\Internal\AmountText;
use Madagascar\MgUtils\ErrorCode;
use Madagascar\MgUtils\MgUtilsException;
use Madagascar\MgUtils\SafeParseResult;
use Madagascar\MgUtils\Words\NumberWords;

/**
 * An amount in whole Malagasy Ariary (MGA).
 *
 * Example:
 *
 *     Ariary::fromInt(1500)->format();                   // 'Ar 1 500'
 *     Ariary::fromInt(1500)->format(Grouping::Dot);      // 'Ar 1.500'
 *     Ariary::parse('1 500 Ar')->amount;            // 1500
 *     Ariary::fromInt(1500)->toWords();                  // 'dimanjato sy arivo ariary'
 *     Ariary::fromFmg(7500)->amount;                // 1500
 */
final readonly class Ariary
{
    private function __construct(
        public int $amount,
    ) {}

    /**
     * An amount in whole ariary, from 0 to 999 999 999 999.
     *
     * Example: `Ariary::fromInt(1500)->amount` is `1500`.
     *
     * @throws MgUtilsException with `ErrorCode::OutOfRange`
     */
    public static function fromInt(int $amount): self
    {
        self::assertInRange($amount, Data::MAX_AMOUNT);

        return new self($amount);
    }

    /**
     * Reads an amount such as `Ar 1 500`, `1.500 Ar`, `MGA 1500` or `1 500`.
     *
     * Example: `Ariary::parse('1.500 Ar')->amount` is `1500`.
     *
     * @throws MgUtilsException with `ErrorCode::InvalidFormat` (commas, decimals, signs) or `ErrorCode::OutOfRange`
     */
    public static function parse(string $text): self
    {
        $amount = AmountText::read($text);
        if ($amount instanceof MgUtilsException) {
            throw $amount;
        }

        return new self($amount);
    }

    /**
     * Like `parse()`, but returns `null` instead of throwing.
     *
     * Example: `Ariary::tryParse('1,500')` returns `null`.
     */
    public static function tryParse(mixed $text): ?self
    {
        return self::safeParse($text)->data;
    }

    /**
     * Like `parse()`, but never throws: returns the amount or the reason it was rejected.
     *
     * Example: `Ariary::safeParse('1,500')->error?->errorCode` is `ErrorCode::InvalidFormat`.
     *
     * @return SafeParseResult<self>
     */
    public static function safeParse(mixed $text): SafeParseResult
    {
        if (!\is_string($text)) {
            return SafeParseResult::failure(new MgUtilsException(ErrorCode::InvalidType, 'Expected the amount as a string.'));
        }
        $amount = AmountText::read($text);

        return \is_int($amount) ? SafeParseResult::success(new self($amount)) : SafeParseResult::failure($amount);
    }

    /**
     * Converts old Malagasy francs (FMG): 5 FMG = 1 Ar.
     *
     * Example: `Ariary::fromFmg(7500)->amount` is `1500`.
     *
     * @throws MgUtilsException with `ErrorCode::OutOfRange` for negative amounts or amounts that are not a multiple of 5
     */
    public static function fromFmg(int $francs): self
    {
        self::assertInRange($francs, Data::MAX_AMOUNT * Data::FRANCS_PER_ARIARY);
        if ($francs % Data::FRANCS_PER_ARIARY !== 0) {
            throw new MgUtilsException(ErrorCode::OutOfRange, \sprintf('Only multiples of %d FMG convert to whole ariary.', Data::FRANCS_PER_ARIARY));
        }

        return new self(intdiv($francs, Data::FRANCS_PER_ARIARY));
    }

    /**
     * Writes an amount in old francs with the same layout as `format()`.
     *
     * Example: `Ariary::formatFmg(7500)` returns `'Fmg 7 500'`.
     *
     * @throws MgUtilsException with `ErrorCode::OutOfRange`
     */
    public static function formatFmg(int $francs, Grouping $grouping = Grouping::DEFAULT, SymbolPosition $position = SymbolPosition::Before): string
    {
        self::assertInRange($francs, Data::MAX_AMOUNT * Data::FRANCS_PER_ARIARY);

        return self::layOut($francs, Data::FRANC_SYMBOL, $grouping, $position);
    }

    /**
     * Writes the amount the Malagasy way: `Ar 1 500` with no-break spaces, never a comma.
     *
     * Example: `Ariary::fromInt(1500)->format(Grouping::Dot, SymbolPosition::After)` returns `'1.500 Ar'`.
     */
    public function format(Grouping $grouping = Grouping::DEFAULT, SymbolPosition $position = SymbolPosition::Before): string
    {
        return self::layOut($this->amount, Data::CURRENCY_SYMBOL, $grouping, $position);
    }

    /**
     * The amount in Malagasy words, followed by `ariary`.
     *
     * Example: `Ariary::fromInt(1500)->toWords()` returns `'dimanjato sy arivo ariary'`.
     */
    public function toWords(): string
    {
        return NumberWords::fromInt($this->amount) . ' ' . Data::CURRENCY_WORD;
    }

    /**
     * The amount in old Malagasy francs: 1 Ar = 5 FMG.
     *
     * Example: `Ariary::fromInt(1500)->toFmg()` returns `7500`.
     */
    public function toFmg(): int
    {
        return $this->amount * Data::FRANCS_PER_ARIARY;
    }

    private static function assertInRange(int $value, int $max): void
    {
        if ($value < Data::MIN_AMOUNT || $value > $max) {
            throw new MgUtilsException(ErrorCode::OutOfRange, \sprintf('Amounts go from %d to %d.', Data::MIN_AMOUNT, $max));
        }
    }

    private static function layOut(int $value, string $symbol, Grouping $grouping, SymbolPosition $position): string
    {
        $separator = $grouping->separator();
        // Split from the right: reverse, cut into groups, then restore each group and their order.
        $groups = array_map(strrev(...), array_reverse(str_split(strrev((string) $value), Data::GROUP_SIZE)));
        $digits = implode($separator, $groups);

        return match ($position) {
            SymbolPosition::Before => $symbol . Data::SYMBOL_SEPARATOR . $digits,
            SymbolPosition::After => $digits . Data::SYMBOL_SEPARATOR . $symbol,
        };
    }
}
