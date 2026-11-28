<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Phone\Generated;

/**
 * Lookup tables from spec/modules/phone/data.yaml (spec 0.10.0).
 *
 * @internal
 */
final class Data
{
    public const string COUNTRY_CODE = '261';

    public const string INTERNATIONAL_PREFIX = '00';

    public const string TRUNK_PREFIX = '0';

    public const int NATIONAL_NUMBER_LENGTH = 9;

    /** @var list<int> */
    public const array GROUPING = [
        2,
        2,
        3,
        2,
    ];

    /** @var array<int|string, string> */
    public const array OPERATOR_BY_PREFIX = [
        '20' => 'yas',
        '22' => 'gulfsat',
        '32' => 'orange',
        '33' => 'airtel',
        '34' => 'yas',
        '35' => 'airtel',
        '36' => 'yas',
        '37' => 'orange',
        '38' => 'yas',
        '39' => 'blueline',
    ];

    /** @var list<string> */
    public const array FIXED_PREFIXES = [
        '20',
        '22',
    ];

    public const int OPERATOR_PREFIX_LENGTH = 2;

    public const string FIXED_ZONE_PREFIX = '20';

    /** @var list<string> */
    public const array FIXED_ZONE_DIGITS = [
        '2',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
    ];

    /** @var list<array{prefix: string, name: string, province: string}> */
    public const array AREAS = [
        [
            'prefix' => '20722',
            'name' => 'Manakara',
            'province' => 'Fianarantsoa',
        ],
        [
            'prefix' => '20729',
            'name' => 'Mananjary',
            'province' => 'Fianarantsoa',
        ],
        [
            'prefix' => '2053',
            'name' => 'Toamasina',
            'province' => 'Toamasina',
        ],
        [
            'prefix' => '2054',
            'name' => 'Ambatondrazaka',
            'province' => 'Toamasina',
        ],
        [
            'prefix' => '2056',
            'name' => 'Moramanga',
            'province' => 'Toamasina',
        ],
        [
            'prefix' => '2057',
            'name' => 'Sainte-Marie',
            'province' => 'Toamasina',
        ],
        [
            'prefix' => '2062',
            'name' => 'Mahajanga',
            'province' => 'Mahajanga',
        ],
        [
            'prefix' => '2067',
            'name' => 'Antsohihy',
            'province' => 'Mahajanga',
        ],
        [
            'prefix' => '2069',
            'name' => 'Maintirano',
            'province' => 'Mahajanga',
        ],
        [
            'prefix' => '2073',
            'name' => 'Farafangana',
            'province' => 'Fianarantsoa',
        ],
        [
            'prefix' => '2075',
            'name' => 'Fianarantsoa',
            'province' => 'Fianarantsoa',
        ],
        [
            'prefix' => '2082',
            'name' => 'Antsiranana',
            'province' => 'Antsiranana',
        ],
        [
            'prefix' => '2086',
            'name' => 'Nosy Be',
            'province' => 'Antsiranana',
        ],
        [
            'prefix' => '2088',
            'name' => 'Sambava',
            'province' => 'Antsiranana',
        ],
        [
            'prefix' => '2092',
            'name' => 'Tolagnaro',
            'province' => 'Toliary',
        ],
        [
            'prefix' => '2094',
            'name' => 'Toliary',
            'province' => 'Toliary',
        ],
        [
            'prefix' => '2095',
            'name' => 'Morondava',
            'province' => 'Toliary',
        ],
        [
            'prefix' => '202',
            'name' => 'Antananarivo',
            'province' => 'Antananarivo',
        ],
    ];
}
