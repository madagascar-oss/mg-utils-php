<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Madagascar\MgUtils\Date;

/**
 * How `MalagasyDate::format()` writes a date. The first case is the default.
 *
 * Example: `MalagasyDate::fromString('2026-11-23')->format(DateStyle::Full)` returns
 * `'Alatsinainy 23 Novambra 2026'`.
 */
enum DateStyle: string
{
    case Long = 'long';
    case Full = 'full';
    case Medium = 'medium';
    case Short = 'short';
    case Numeric = 'numeric';

    /**
     * The CLDR pattern of this style, e.g. `d MMMM y` for `DateStyle::Long`.
     */
    public function pattern(): string
    {
        return match ($this) {
            self::Long => 'd MMMM y',
            self::Full => 'EEEE d MMMM y',
            self::Medium => 'y MMM d',
            self::Short => 'y-MM-dd',
            self::Numeric => 'dd/MM/y',
        };
    }
}
