<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Benchmarks;

use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Mdgascar\MdgUtils\Phone\PhoneNumberFormat;

final class PhoneNumberBench
{
    private const string NATIONAL_WITH_SPACES = '034 12 345 67';
    private const string E164 = '+261341234567';

    public function benchParseNationalWithSpaces(): void
    {
        PhoneNumber::parse(self::NATIONAL_WITH_SPACES);
    }

    public function benchFormatInternational(): string
    {
        return PhoneNumber::parse(self::E164)->format(PhoneNumberFormat::International);
    }
}
