<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Words\Internal;

/**
 * Where a number is written: as a whole number, whose groups join with "sy" (rule 8), or as a
 * count before a group word, one phrase joined by "amby" (rule 6).
 *
 * @internal not part of the public API
 */
enum Role
{
    case Number;
    case Count;
}
