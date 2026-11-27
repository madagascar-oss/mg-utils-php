<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils;

use Mdgascar\MdgUtils\Internal\DecimalText;

/**
 * A number read from text, kept as its exact decimal digits. `value()` gives an int or float
 * only when it holds every digit; it never rounds.
 *
 * Example:
 *
 *     NumberWords::parse('roa arivo')->value();                 // 2000
 *     NumberWords::parse('folo lavitrisa lavitrisa')->digits;   // '10000000000000000000'
 */
final readonly class ExactNumber
{
    /**
     * @param string $digits canonical decimal text: no leading zeros, no trailing decimal zeros
     */
    private function __construct(
        public string $digits,
    ) {}

    /**
     * @internal built by the readers
     */
    public static function fromDigits(string $digits): self
    {
        return new self($digits);
    }

    /**
     * The number as an int (whole, up to PHP_INT_MAX) or a float (with decimals it holds exactly).
     *
     * @throws MdgUtilsException with `ErrorCode::PrecisionLoss` when neither holds every digit;
     *                          read `digits` instead
     */
    public function value(): int|float
    {
        return DecimalText::toNumber($this->digits) ?? throw new MdgUtilsException(
            ErrorCode::PrecisionLoss,
            \sprintf('%s does not fit an int or float exactly; read ->digits for the exact digits.', $this->digits),
        );
    }
}
