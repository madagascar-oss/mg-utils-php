<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Date\Generated;

/**
 * Lookup tables from spec/modules/date/data.yaml (spec 0.10.0).
 *
 * @internal
 */
final class Data
{
    /** @var list<string> */
    public const array MONTH_NAMES = [
        '',
        'Janoary',
        'Febroary',
        'Martsa',
        'Aprily',
        'Mey',
        'Jona',
        'Jolay',
        'Aogositra',
        'Septambra',
        'Oktobra',
        'Novambra',
        'Desambra',
    ];

    /** @var list<string> */
    public const array MONTH_ABBREVIATIONS = [
        '',
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'Mey',
        'Jon',
        'Jol',
        'Aog',
        'Sep',
        'Okt',
        'Nov',
        'Des',
    ];

    /** @var list<string> */
    public const array WEEKDAY_NAMES = [
        '',
        'Alatsinainy',
        'Talata',
        'Alarobia',
        'Alakamisy',
        'Zoma',
        'Asabotsy',
        'Alahady',
    ];

    /** @var list<string> */
    public const array WEEKDAY_ABBREVIATIONS = [
        '',
        'Alats',
        'Tal',
        'Alar',
        'Alak',
        'Zom',
        'Asab',
        'Alah',
    ];

    public const int MIN_YEAR = 1;

    public const int MAX_YEAR = 9999;

    public const int MAX_READ_LENGTH = 64;

    public const int MAX_TIME_READ_LENGTH = 128;

    public const string TIME_PREFIX = 'amin\'ny';

    public const string TIME_AFTER = 'sy';

    public const string TIME_BEFORE = 'latsaka';

    public const string TIME_MORE = 'mahery';

    /** @var array<int, string> */
    public const array TIME_FRACTIONS = [
        '15' => 'fahefany',
        '20' => 'fahatelony',
        '30' => 'sasany',
    ];

    /** @var list<string> */
    public const array DAY_PARTS = [
        'alina',
        'maraina',
        'maraina',
        'maraina',
        'maraina',
        'maraina',
        'maraina',
        'maraina',
        'maraina',
        'maraina',
        'maraina',
        'maraina',
        'atoandro',
        'tolakandro',
        'tolakandro',
        'tolakandro',
        'tolakandro',
        'tolakandro',
        'hariva',
        'hariva',
        'hariva',
        'hariva',
        'hariva',
        'hariva',
    ];
}
