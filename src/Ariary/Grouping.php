<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Madagascar\MgUtils\Ariary;

/**
 * Thousand separator for formatting and reading amounts. Madagascar uses a space or a dot;
 * `Comma` is only for text in another convention (with `DecimalSeparator::Dot`).
 *
 * Example: `Ariary::fromInt(1500)->format(Grouping::Dot)` returns `'Ar 1.500'`.
 */
enum Grouping: string
{
    case Space = 'space';
    case Dot = 'dot';
    case Comma = 'comma';

    /** The option used when none is given: the first one in the spec, the Malagasy way. */
    public const self DEFAULT = self::Space;

    /**
     * The separator character.
     *
     * @return non-empty-string
     */
    public function separator(): string
    {
        return match ($this) {
            self::Space => "\u{00a0}",
            self::Dot => '.',
            self::Comma => ',',
        };
    }
}
