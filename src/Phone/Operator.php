<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Phone;

/**
 * A Malagasy telephone operator, identified by the same id in every language.
 *
 * Example: `PhoneNumber::parse('034 12 345 67')->operator === Operator::Yas`
 */
enum Operator: string
{
    case Orange = 'orange';
    case Airtel = 'airtel';
    case Yas = 'yas';
    case Blueline = 'blueline';
    case Gulfsat = 'gulfsat';

    /**
     * Display name of the operator, e.g. `Yas Madagascar`.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::Orange => 'Orange Madagascar',
            self::Airtel => 'Airtel Madagascar',
            self::Yas => 'Yas Madagascar',
            self::Blueline => 'Blueline',
            self::Gulfsat => 'Gulfsat Madagascar',
        };
    }
}
