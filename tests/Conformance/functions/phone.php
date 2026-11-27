<?php

declare(strict_types=1);

use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Mdgascar\MdgUtils\Phone\PhoneNumberFormat;

/**
 * @return array{e164: string, nationalNumber: string, operator: string, type: string, area: array{name: string, province: string}|null}
 */
$toArray = static fn(PhoneNumber $phone): array => [
    'e164' => $phone->e164,
    'nationalNumber' => $phone->nationalNumber,
    'operator' => $phone->operator->value,
    'type' => $phone->type->value,
    'area' => $phone->area === null ? null : ['name' => $phone->area->name, 'province' => $phone->area->province],
];

// Arguments arrive untyped from YAML on purpose: wrong types fail with TypeError and unknown
// format names with ValueError, which this port reports as INVALID_TYPE (docs/adr/0002).
return [
    // @phpstan-ignore argument.type
    'parsePhone' => static fn(mixed $input): array => $toArray(PhoneNumber::parse($input)),
    'formatPhone' => static fn(mixed $input, mixed $format): string => PhoneNumber::parse($input) // @phpstan-ignore argument.type
        ->format(PhoneNumberFormat::from($format)), // @phpstan-ignore argument.type
    'isValidPhone' => static fn(mixed $input): bool => PhoneNumber::isValid($input),
];
