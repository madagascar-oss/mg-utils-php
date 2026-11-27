<?php

declare(strict_types=1);

use Mdgascar\MdgUtils\Words\NumberSystem;
use Mdgascar\MdgUtils\Words\NumberWords;

// Arguments arrive untyped from YAML on purpose: wrong types (null, a list) fail with TypeError,
// which this port reports as INVALID_TYPE (mdg-utils-spec docs/adr/0002-php-port.md). The spec
// option `{system}` is the NumberSystem argument; an unknown system fails with ValueError, also
// INVALID_TYPE.
$system = static fn(array $options): NumberSystem => isset($options['system'])
    // @phpstan-ignore argument.type
    ? NumberSystem::from($options['system'])
    : NumberSystem::Standard;

return [
    // @phpstan-ignore argument.type
    'spell' => static fn(mixed $value, array $options = []): string => NumberWords::spell($value, $system($options)),
];
