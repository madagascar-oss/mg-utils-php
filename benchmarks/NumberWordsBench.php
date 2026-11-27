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
        NumberWords::spell(self::SMALL_NUMBER);
    }

    public function benchDecimal(): void
    {
        NumberWords::spell(self::DECIMAL_NUMBER);
    }

    public function benchLargest(): void
    {
        NumberWords::spell(PHP_INT_MAX);
    }
}
