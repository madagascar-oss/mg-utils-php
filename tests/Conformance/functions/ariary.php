<?php

declare(strict_types=1);

use Madagascar\MgUtils\Ariary\Ariary;
use Madagascar\MgUtils\Ariary\Grouping;
use Madagascar\MgUtils\Ariary\SymbolPosition;

/**
 * Spec options `{grouping, symbolPosition}` → PHP enums. Unknown names raise ValueError,
 * which this port reports as INVALID_TYPE (docs/adr/0002).
 *
 * @return array{Grouping, SymbolPosition}
 */
$layout = static fn(array $options): array => [
    // @phpstan-ignore argument.type
    isset($options['grouping']) ? Grouping::from($options['grouping']) : Grouping::DEFAULT,
    // @phpstan-ignore argument.type
    SymbolPosition::from($options['symbolPosition'] ?? SymbolPosition::Before->value),
];

// Arguments arrive untyped from YAML on purpose: wrong types fail with TypeError (INVALID_TYPE).
return [
    // @phpstan-ignore argument.type
    'formatAriary' => static fn(mixed $amount, array $options = []): string => Ariary::fromInt($amount)->format(...$layout($options)),
    // @phpstan-ignore argument.type
    'parseAriary' => static fn(mixed $text): int => Ariary::parse($text)->amount,
    // @phpstan-ignore argument.type
    'ariaryToWords' => static fn(mixed $amount): string => Ariary::fromInt($amount)->toWords(),
    // @phpstan-ignore argument.type
    'fmgToAriary' => static fn(mixed $francs): int => Ariary::fromFmg($francs)->amount,
    // @phpstan-ignore argument.type
    'ariaryToFmg' => static fn(mixed $amount): int => Ariary::fromInt($amount)->toFmg(),
    // @phpstan-ignore argument.type
    'formatFmg' => static fn(mixed $francs, array $options = []): string => Ariary::formatFmg($francs, ...$layout($options)),
];
