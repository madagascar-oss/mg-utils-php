<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Benchmarks;

use Madagascar\MgUtils\Words\NumberWords;

final class NumberWordsBench
{
    private const int SMALL_NUMBER = 21;
    private const int LARGEST_SUPPORTED_NUMBER = 999_999_999_999;

    public function benchSmall(): void
    {
        NumberWords::fromInt(self::SMALL_NUMBER);
    }

    public function benchLargest(): void
    {
        NumberWords::fromInt(self::LARGEST_SUPPORTED_NUMBER);
    }
}
