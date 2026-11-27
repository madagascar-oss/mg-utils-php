<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Words\Generated;

/**
 * Lookup tables from spec/modules/words/data.yaml (spec 0.7.0).
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

    /** @var non-empty-list<array{word: string, exponent: int, countDigits: int|null}> */
    public const array SCALES = [
        [
            'word' => 'arivo',
            'exponent' => 3,
            'countDigits' => 1,
        ],
        [
            'word' => 'alina',
            'exponent' => 4,
            'countDigits' => 1,
        ],
        [
            'word' => 'hetsy',
            'exponent' => 5,
            'countDigits' => 1,
        ],
        [
            'word' => 'tapitrisa',
            'exponent' => 6,
            'countDigits' => 3,
        ],
        [
            'word' => 'lavitrisa',
            'exponent' => 9,
            'countDigits' => null,
        ],
    ];

    /** @var non-empty-list<array{word: string, exponent: int, countDigits: int|null}> */
    public const array TRADITIONAL_SCALES = [
        [
            'word' => 'arivo',
            'exponent' => 3,
            'countDigits' => 1,
        ],
        [
            'word' => 'alina',
            'exponent' => 4,
            'countDigits' => 1,
        ],
        [
            'word' => 'hetsy',
            'exponent' => 5,
            'countDigits' => 1,
        ],
        [
            'word' => 'tapitrisa',
            'exponent' => 6,
            'countDigits' => 1,
        ],
        [
            'word' => 'safatsiroa',
            'exponent' => 7,
            'countDigits' => 1,
        ],
        [
            'word' => 'tsitamboisa',
            'exponent' => 8,
            'countDigits' => 1,
        ],
        [
            'word' => 'lavitrisa',
            'exponent' => 9,
            'countDigits' => 1,
        ],
        [
            'word' => 'alinkisa',
            'exponent' => 10,
            'countDigits' => 1,
        ],
        [
            'word' => 'tsipesimpesenina',
            'exponent' => 11,
            'countDigits' => 1,
        ],
        [
            'word' => 'tsitokotsiforohana',
            'exponent' => 12,
            'countDigits' => 1,
        ],
        [
            'word' => 'tsihitanoanoa',
            'exponent' => 13,
            'countDigits' => 1,
        ],
        [
            'word' => 'safatsiroafaharoa',
            'exponent' => 14,
            'countDigits' => 1,
        ],
        [
            'word' => 'tsitamboisafaharoa',
            'exponent' => 15,
            'countDigits' => 1,
        ],
        [
            'word' => 'lavitrisafaharoa',
            'exponent' => 16,
            'countDigits' => 1,
        ],
        [
            'word' => 'alinkisafaharoa',
            'exponent' => 17,
            'countDigits' => 1,
        ],
        [
            'word' => 'tsipesimpeseninafaharoa',
            'exponent' => 18,
            'countDigits' => null,
        ],
    ];

    public const string DECIMAL_SEPARATOR = 'faingo';

    public const string JOINER_UNITS_BEFORE_TEN = 'ambin\'ny';

    public const string JOINER_BELOW_THOUSAND = 'amby';

    public const string JOINER_BETWEEN_GROUPS = 'sy';

    public const int MIN_VALUE = 0;
}
