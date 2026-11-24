<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Madagascar\MgUtils\Ariary;

/**
 * Thousand separator used when formatting amounts. Madagascar never uses a comma.
 *
 * Example: `Ariary::fromInt(1500)->format(Grouping::Dot)` returns `'Ar 1.500'`.
 */
enum Grouping: string
{
    case Space = 'space';
    case Dot = 'dot';

    /**
     * The separator character, e.g. a no-break space for `Grouping::Space`.
     */
    public function separator(): string
    {
        return match ($this) {
            self::Space => "\u{00a0}",
            self::Dot => '.',
        };
    }
}
