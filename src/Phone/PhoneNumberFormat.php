<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Phone;

/**
 * How `PhoneNumber::format()` writes a number.
 *
 * Example: `PhoneNumber::parse('0341234567')->format(PhoneNumberFormat::National)` returns
 * `'034 12 345 67'`.
 */
enum PhoneNumberFormat: string
{
    /** `+261341234567` */
    case E164 = 'e164';

    /** `+261 34 12 345 67` */
    case International = 'international';

    /** `034 12 345 67` */
    case National = 'national';
}
