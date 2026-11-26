<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Madagascar\MgUtils\Ariary\Generated;

/**
 * Lookup tables from spec/modules/ariary/data.yaml (spec 0.5.0).
 *
 * @internal
 */
final class Data
{
    public const string CURRENCY_SYMBOL = 'Ar';

    public const string CURRENCY_WORD = 'ariary';

    public const string SYMBOL_SEPARATOR = "\u{00a0}";

    public const int GROUP_SIZE = 3;

    /** @var list<string> */
    public const array READABLE_SEPARATORS = [
        ' ',
        "\u{00a0}",
        "\u{202f}",
        '.',
    ];

    /** @var list<string> */
    public const array CURRENCY_TOKENS = [
        'ariary',
        'mga',
        'ar',
    ];

    public const string FRANC_SYMBOL = 'Fmg';

    public const int FRANCS_PER_ARIARY = 5;

    public const int FRANC_DIVISION_FACTOR = 2;

    public const int MIN_AMOUNT = 0;
}
