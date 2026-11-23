<?php

declare(strict_types=1);

use Madagascar\MgUtils\Phone\PhoneNumber;
use Madagascar\MgUtils\Phone\PhoneNumberFormat;

/**
 * @return array{e164: string, nationalNumber: string, operator: string}
 */
$toArray = static fn(PhoneNumber $phone): array => [
    'e164' => $phone->e164,
    'nationalNumber' => $phone->nationalNumber,
    'operator' => $phone->operator->value,
];

// Arguments arrive untyped from YAML on purpose: wrong types fail with TypeError and unknown
// format names with ValueError, which this port reports as INVALID_TYPE (docs/adr/0002).
return [
    // @phpstan-ignore argument.type
    'parsePhoneNumber' => static fn(mixed $input): array => $toArray(PhoneNumber::parse($input)),
    'formatPhoneNumber' => static fn(mixed $input, mixed $format): string => PhoneNumber::parse($input) // @phpstan-ignore argument.type
        ->format(PhoneNumberFormat::from($format)), // @phpstan-ignore argument.type
    'isValidPhoneNumber' => static fn(mixed $input): bool => PhoneNumber::isValid($input),
];
