<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Ariary;

/**
 * How the currency is shown: its symbol (`Ar 1 500`) or its ISO 4217 code (`MGA 1 500`; `MGF`
 * for old francs).
 *
 * Example: `Ariary::fromInt(1500)->format(currency: CurrencyDisplay::Code)` returns `'MGA 1 500'`.
 */
enum CurrencyDisplay: string
{
    case Symbol = 'symbol';
    case Code = 'code';
}
