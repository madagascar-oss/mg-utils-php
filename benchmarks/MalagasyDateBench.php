<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Benchmarks;

use Madagascar\MgUtils\Date\DateStyle;
use Madagascar\MgUtils\Date\MalagasyDate;

final class MalagasyDateBench
{
    private const string SAMPLE_DATE = '2026-11-23';

    public function benchFormatFull(): string
    {
        return MalagasyDate::fromString(self::SAMPLE_DATE)->format(DateStyle::Full);
    }
}
