<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Phone;

/**
 * What kind of line a number reaches: mobile (codes 3x) or fixed (codes 2x).
 *
 * Example: `PhoneNumber::parse('020 22 421 19')->type === PhoneLineType::Fixed`
 */
enum PhoneLineType: string
{
    case Mobile = 'mobile';
    case Fixed = 'fixed';
}
