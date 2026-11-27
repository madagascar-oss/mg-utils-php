<?php

declare(strict_types=1);

use Mdgascar\MdgUtils\Words\NumberSystem;
use Mdgascar\MdgUtils\Words\NumberWords;

// Arguments arrive untyped from YAML on purpose: wrong types fail with TypeError, which this
// port reports as INVALID_TYPE (mdg-utils-spec docs/adr/0002-php-port.md). YAML floats
// (3.14, .nan) go to fromFloat, text ('12') to fromString, everything else to fromInt. The spec option `{system}` is the
// NumberSystem argument; an unknown system fails with ValueError, also INVALID_TYPE.
$system = static fn(array $options): NumberSystem => isset($options['system'])
    // @phpstan-ignore argument.type
    ? NumberSystem::from($options['system'])
    : NumberSystem::Standard;

return [
    'numberToWords' => static fn(mixed $value, array $options = []): string => match (true) {
        is_string($value) => NumberWords::fromString($value, $system($options)),
        is_float($value) => NumberWords::fromFloat($value, $system($options)),
        // @phpstan-ignore argument.type
        default => NumberWords::fromInt($value, $system($options)),
    },
];
