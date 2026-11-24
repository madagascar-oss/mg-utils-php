<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Date;

/**
 * Width of a month or weekday name.
 *
 * Example: `MalagasyDate::monthName(11, NameWidth::Abbreviated)` returns `'Nov'`.
 */
enum NameWidth: string
{
    /** `Novambra` (default) */
    case Wide = 'wide';

    /** `Nov` */
    case Abbreviated = 'abbreviated';
}
