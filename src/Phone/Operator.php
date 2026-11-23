<?php

// GENERATED — edit spec/ instead. Run `just codegen` to refresh.

declare(strict_types=1);

namespace Madagascar\MgUtils\Phone;

/**
 * A Malagasy mobile operator, identified by the same id in every language.
 *
 * Example: `PhoneNumber::parse('034 12 345 67')->operator === Operator::Telma`
 */
enum Operator: string
{
    case Orange = 'orange';
    case Airtel = 'airtel';
    case Telma = 'telma';
    case Blueline = 'blueline';

    /**
     * Display name of the operator, e.g. `Telma`.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::Orange => 'Orange Madagascar',
            self::Airtel => 'Airtel Madagascar',
            self::Telma => 'Telma',
            self::Blueline => 'Blueline',
        };
    }
}
