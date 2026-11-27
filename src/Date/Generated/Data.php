<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Date\Generated;

/**
 * Lookup tables from spec/modules/date/data.yaml (spec 0.7.0).
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
}
