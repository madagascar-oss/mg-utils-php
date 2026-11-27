<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Phone;

/**
 * The town of a geographic fixed number, as the national numbering plan lists it.
 *
 * Example: `PhoneNumber::parse('020 53 123 45')->area?->name` is `'Toamasina'`.
 */
final readonly class PhoneArea
{
    /**
     * @internal built by `PhoneNumber::parse()`
     */
    public function __construct(
        /** Town, e.g. `Toamasina`. */
        public string $name,
        /** Former province, e.g. `Toamasina`. */
        public string $province,
    ) {}
}
