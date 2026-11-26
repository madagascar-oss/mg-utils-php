<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Mdgascar\MdgUtils;

/**
 * Error code shared by every mdg-utils language port.
 */
enum ErrorCode: string
{
    /** The input is not of the accepted type (for example a string or a non-integer where an integer is expected). */
    case InvalidType = 'INVALID_TYPE';

    /** The input has the right type but lies outside the supported range. */
    case OutOfRange = 'OUT_OF_RANGE';

    /** The input text cannot be read as a value of this module (wrong characters or wrong length). */
    case InvalidFormat = 'INVALID_FORMAT';

    /** The phone number is well formed but its operator code is not assigned to a mobile operator. */
    case UnknownOperator = 'UNKNOWN_OPERATOR';
}
