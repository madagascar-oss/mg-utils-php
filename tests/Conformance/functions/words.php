<?php

declare(strict_types=1);

use Madagascar\MgUtils\Words\NumberWords;

// Arguments arrive untyped from YAML on purpose: wrong types fail with TypeError, which this
// port reports as INVALID_TYPE (mg-utils-spec docs/adr/0002-php-port.md). YAML floats
// (3.14, .nan) go to fromFloat, everything else to fromInt.
return [
    'numberToWords' => static fn(mixed $value): string => is_float($value)
        ? NumberWords::fromFloat($value)
        // @phpstan-ignore argument.type
        : NumberWords::fromInt($value),
];
