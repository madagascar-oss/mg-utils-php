<?php

declare(strict_types=1);

use Madagascar\MgUtils\Words\NumberWords;

// Arguments arrive untyped from YAML on purpose: wrong types fail with TypeError, which this
// port reports as INVALID_TYPE (docs/adr/0002-php-port.md).
return [
    // @phpstan-ignore argument.type
    'numberToWords' => static fn(mixed $value): string => NumberWords::fromInt($value),
];
