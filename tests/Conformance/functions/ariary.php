<?php

declare(strict_types=1);

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;
use Mdgascar\MdgUtils\Ariary\SymbolPosition;
use Mdgascar\MdgUtils\Words\NumberSystem;

// Spec options → PHP enums. Unknown names raise ValueError, which this port reports as
// INVALID_TYPE (docs/adr/0002).
$grouping = static fn(array $options): ?Grouping => isset($options['grouping'])
    // @phpstan-ignore argument.type
    ? Grouping::from($options['grouping'])
    : null;
$decimal = static fn(array $options): DecimalSeparator => isset($options['decimal'])
    // @phpstan-ignore argument.type
    ? DecimalSeparator::from($options['decimal'])
    : DecimalSeparator::DEFAULT;

/**
 * Spec options `{grouping, decimal, symbolPosition}` → the arguments of `format()`.
 *
 * @return array{Grouping, SymbolPosition, DecimalSeparator}
 */
$layout = static fn(array $options): array => [
    $grouping($options) ?? Grouping::DEFAULT,
    // @phpstan-ignore argument.type
    SymbolPosition::from($options['symbolPosition'] ?? SymbolPosition::Before->value),
    $decimal($options),
];

/**
 * Spec options `{grouping, decimal}` → the arguments of `parse()`. Reading takes no other option.
 *
 * @return array{?Grouping, DecimalSeparator}
 */
$reading = static function (array $options) use ($grouping, $decimal): array {
    if (array_diff(array_keys($options), ['grouping', 'decimal']) !== []) {
        throw new ValueError('Reading takes only the grouping and decimal options.');
    }

    return [$grouping($options), $decimal($options)];
};

// YAML floats (1.5, .nan) go to fromFloat, everything else to fromInt.
$amount = static fn(mixed $value): Ariary => is_float($value)
    ? Ariary::fromFloat($value)
    // @phpstan-ignore argument.type
    : Ariary::fromInt($value);

// Arguments arrive untyped from YAML on purpose: wrong types fail with TypeError (INVALID_TYPE).
return [
    'formatAriary' => static fn(mixed $value, array $options = []): string => $amount($value)->format(...$layout($options)),
    // @phpstan-ignore argument.type
    'parseAriary' => static fn(mixed $text, array $options = []): int|float => Ariary::parse($text, ...$reading($options))->amount,
    'spellAriary' => static function (mixed $value, array $options = []) use ($amount): string {
        // @phpstan-ignore argument.type
        $system = isset($options['system']) ? NumberSystem::from($options['system']) : NumberSystem::Standard;

        return is_string($value) ? Ariary::spellText($value, $system) : $amount($value)->spell($system);
    },
    // @phpstan-ignore argument.type
    'fmgToAriary' => static fn(mixed $francs): int|float => Ariary::fromFmg($francs)->amount,
    'ariaryToFmg' => static fn(mixed $value): int|float => $amount($value)->toFmg(),
    // @phpstan-ignore argument.type
    'formatFmg' => static fn(mixed $francs, array $options = []): string => Ariary::formatFmg($francs, ...$layout($options)),
];
