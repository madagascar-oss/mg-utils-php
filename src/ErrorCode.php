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

    /** The phone number is well formed but its operator code is not assigned to an operator. */
    case UnknownOperator = 'UNKNOWN_OPERATOR';

    /** The value is exact in the text but the port's number type cannot hold it exactly; ask for the exact text instead. */
    case PrecisionLoss = 'PRECISION_LOSS';

    /** The text reads as more than one value, so none is chosen. */
    case Ambiguous = 'AMBIGUOUS';
}
