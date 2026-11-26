<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Madagascar\MgUtils\Ariary;

/**
 * Decimal separator for formatting and reading amounts. Madagascar uses a comma; `Dot` is
 * only for text in another convention.
 *
 * Example: `Ariary::fromFloat(1500.5)->format()` returns `'Ar 1 500,5'`.
 */
enum DecimalSeparator: string
{
    case Comma = 'comma';
    case Dot = 'dot';

    /** The option used when none is given: the first one in the spec, the Malagasy way. */
    public const self DEFAULT = self::Comma;

    /**
     * The separator character.
     *
     * @return non-empty-string
     */
    public function separator(): string
    {
        return match ($this) {
            self::Comma => ',',
            self::Dot => '.',
        };
    }
}
