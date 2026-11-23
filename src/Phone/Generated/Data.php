<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Madagascar\MgUtils\Phone\Generated;

/**
 * Lookup tables from spec/phone/data.yaml (spec 0.1.0).
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
        '32' => 'orange',
        '33' => 'airtel',
        '34' => 'telma',
        '37' => 'orange',
        '38' => 'telma',
        '39' => 'blueline',
    ];

    public const int OPERATOR_PREFIX_LENGTH = 2;
}
