<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Ariary;

/**
 * Where the currency symbol goes when formatting.
 *
 * Example: `Ariary::fromInt(1500)->format(position: SymbolPosition::After)` returns `'1 500 Ar'`.
 */
enum SymbolPosition: string
{
    /** `Ar 1 500` (default, as in CLDR) */
    case Before = 'before';

    /** `1 500 Ar` */
    case After = 'after';
}
