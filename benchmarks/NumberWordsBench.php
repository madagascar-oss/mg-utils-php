<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Benchmarks;

use Mdgascar\MdgUtils\Words\NumberWords;

final class NumberWordsBench
{
    private const int SMALL_NUMBER = 21;
    private const float DECIMAL_NUMBER = 1234.5678;

    public function benchSmall(): void
    {
        NumberWords::fromInt(self::SMALL_NUMBER);
    }

    public function benchDecimal(): void
    {
        NumberWords::fromFloat(self::DECIMAL_NUMBER);
    }

    public function benchLargest(): void
    {
        NumberWords::fromInt(PHP_INT_MAX);
    }
}
