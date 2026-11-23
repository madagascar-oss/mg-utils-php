<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Tests\Words;

use Madagascar\MgUtils\ErrorCode;
use Madagascar\MgUtils\MgUtilsException;
use Madagascar\MgUtils\Words\NumberWords;
use PHPUnit\Framework\TestCase;

final class NumberWordsTest extends TestCase
{
    private const int RANDOM_SEED = 2026;
    private const int FUZZ_RUNS = 2000;
    // Largest supported input (spec/words/data.yaml limits.max).
    private const int LARGEST_SUPPORTED_NUMBER = 999_999_999_999;

    public function testReturnsLowercaseWordsForRandomSupportedIntegers(): void
    {
        mt_srand(self::RANDOM_SEED);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $words = NumberWords::fromInt(mt_rand(0, self::LARGEST_SUPPORTED_NUMBER));

            self::assertMatchesRegularExpression("/^[a-z' ]+$/", $words);
            self::assertDoesNotMatchRegularExpression('/ {2}|^ | $/', $words);
        }
    }

    public function testThrowsOutOfRangeWithTheSharedCode(): void
    {
        try {
            NumberWords::fromInt(PHP_INT_MAX);
            self::fail('Expected MgUtilsException');
        } catch (MgUtilsException $exception) {
            self::assertSame(ErrorCode::OutOfRange, $exception->errorCode);
        }
    }
}
