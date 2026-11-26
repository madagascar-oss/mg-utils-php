<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Benchmarks;

use Mdgascar\MdgUtils\Date\DateStyle;
use Mdgascar\MdgUtils\Date\MalagasyDate;

final class MalagasyDateBench
{
    private const string SAMPLE_DATE = '2026-11-23';

    public function benchFormatFull(): string
    {
        return MalagasyDate::fromString(self::SAMPLE_DATE)->format(DateStyle::Full);
    }
}
