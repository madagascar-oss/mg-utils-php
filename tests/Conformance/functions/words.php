<?php

declare(strict_types=1);

use Mdgascar\MdgUtils\ExactNumber;
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

// The spec option `{as}` picks the exact digits (`text`) or the value (`number`, the default).
$asResult = static function (ExactNumber $number, array $options): int|float|string {
    if (array_diff(array_keys($options), ['as']) !== []) {
        throw new ValueError('Reading takes only the as option.');
    }

    return match ($options['as'] ?? 'number') {
        'number' => $number->value(),
        'text' => $number->digits,
        default => throw new ValueError('as is number or text.'),
    };
};

return [
    // @phpstan-ignore argument.type
    'spell' => static fn(mixed $value, array $options = []): string => NumberWords::spell($value, $system($options)),
    // @phpstan-ignore argument.type
    'spellOrdinal' => static fn(mixed $value, array $options = []): string => NumberWords::spellOrdinal($value, $system($options)),
    // @phpstan-ignore argument.type
    'parseWords' => static fn(mixed $text, array $options = []): int|float|string => $asResult(NumberWords::parse($text), $options),
];
