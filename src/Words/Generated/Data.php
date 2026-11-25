<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Madagascar\MgUtils\Words\Generated;

/**
 * Lookup tables from spec/modules/words/data.yaml (spec 0.4.0).
 *
 * @internal
 */
final class Data
{
    public const string ZERO_WORD = 'aotra';

    /** @var list<string> */
    public const array UNIT_WORDS = [
        '',
        'iray',
        'roa',
        'telo',
        'efatra',
        'dimy',
        'enina',
        'fito',
        'valo',
        'sivy',
    ];

    public const string UNIT_ONE_IN_COMPOUND = 'iraika';

    /** @var list<string> */
    public const array TEN_WORDS = [
        '',
        'folo',
        'roapolo',
        'telopolo',
        'efapolo',
        'dimampolo',
        'enimpolo',
        'fitopolo',
        'valopolo',
        'sivifolo',
    ];

    /** @var list<string> */
    public const array HUNDRED_WORDS = [
        '',
        'zato',
        'roanjato',
        'telonjato',
        'efajato',
        'dimanjato',
        'eninjato',
        'fitonjato',
        'valonjato',
        'sivinjato',
    ];

    /** @var list<array{value: int, word: string, maxCount: int}> */
    public const array SCALES = [
        [
            'value' => 1000,
            'word' => 'arivo',
            'maxCount' => 9,
        ],
        [
            'value' => 10000,
            'word' => 'alina',
            'maxCount' => 9,
        ],
        [
            'value' => 100000,
            'word' => 'hetsy',
            'maxCount' => 9,
        ],
        [
            'value' => 1000000,
            'word' => 'tapitrisa',
            'maxCount' => 999,
        ],
        [
            'value' => 1000000000,
            'word' => 'lavitrisa',
            'maxCount' => 999,
        ],
    ];

    public const string JOINER_UNITS_BEFORE_TEN = 'ambin\'ny';

    public const string JOINER_BELOW_THOUSAND = 'amby';

    public const string JOINER_BETWEEN_GROUPS = 'sy';

    public const int MIN_VALUE = 0;

    public const int MAX_VALUE = 999999999999;
}
