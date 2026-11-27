<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Benchmarks;

use Mdgascar\MdgUtils\Ariary\Ariary;

final class AriaryBench
{
    private const int SAMPLE_AMOUNT = 2_026_000;
    private const string SAMPLE_TEXT = 'Ar 2 026 000';

    public function benchFormat(): string
    {
        return Ariary::fromInt(self::SAMPLE_AMOUNT)->format();
    }

    public function benchParse(): int|float
    {
        return Ariary::parse(self::SAMPLE_TEXT)->amount();
    }
}
