<?php

declare(strict_types=1);

use Mdgascar\MdgUtils\Date\DateStyle;
use Mdgascar\MdgUtils\Date\MalagasyDate;
use Mdgascar\MdgUtils\Date\NameWidth;

// Arguments arrive untyped from YAML on purpose: wrong types fail with TypeError and unknown
// style or width names with ValueError, which this port reports as INVALID_TYPE (docs/adr/0002).
$toDate = static fn(mixed $date): MalagasyDate => is_array($date)
    // @phpstan-ignore argument.type, argument.type, argument.type
    ? MalagasyDate::fromParts($date['year'], $date['month'], $date['day'])
    // @phpstan-ignore argument.type
    : MalagasyDate::fromString($date);

return [
    'formatDate' => static fn(mixed $date, ?string $style = null): string => $toDate($date)
        ->format($style === null ? DateStyle::DEFAULT : DateStyle::from($style)),
    'monthName' => static fn(mixed $month, ?string $width = null): string => MalagasyDate::monthName(
        // @phpstan-ignore argument.type
        $month,
        $width === null ? NameWidth::Wide : NameWidth::from($width),
    ),
    'weekdayName' => static fn(mixed $weekday, ?string $width = null): string => MalagasyDate::weekdayName(
        // @phpstan-ignore argument.type
        $weekday,
        $width === null ? NameWidth::Wide : NameWidth::from($width),
    ),
];
