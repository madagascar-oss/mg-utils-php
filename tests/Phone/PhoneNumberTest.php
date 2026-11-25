<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Tests\Phone;

use Madagascar\MgUtils\ErrorCode;
use Madagascar\MgUtils\MgUtilsException;
use Madagascar\MgUtils\Phone\Operator;
use Madagascar\MgUtils\Phone\PhoneNumber;
use Madagascar\MgUtils\Phone\PhoneNumberFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    private const int RANDOM_SEED = 2026;
    private const int FUZZ_RUNS = 2000;
    // Longer than most phone spellings, short enough to stay under the 32-character limit often.
    private const int FUZZ_MAX_INPUT_LENGTH = 20;
    private const string TELMA_NUMBER_WITH_NEW_PREFIX = '0381234567';
    // A phone number given as an integer instead of a string.
    private const int NUMBER_NOT_A_STRING = 341234567;

    public function testTryParseReturnsThePhoneNumberForValidInput(): void
    {
        self::assertSame(Operator::Telma, PhoneNumber::tryParse('034 12 345 67')?->operator);
    }

    public function testTryParseReturnsNullInsteadOfThrowing(): void
    {
        self::assertNull(PhoneNumber::tryParse('035 12 345 67'));
    }

    public function testTryParseReturnsNullForNonStrings(): void
    {
        self::assertNull(PhoneNumber::tryParse(self::NUMBER_NOT_A_STRING));
    }

    public function testSafeParseReturnsThePhoneNumberOnSuccess(): void
    {
        $result = PhoneNumber::safeParse('034 12 345 67');

        self::assertTrue($result->success);
        self::assertSame(Operator::Telma, $result->data?->operator);
        self::assertNull($result->error);
    }

    public function testSafeParseReturnsTheErrorCodeInsteadOfThrowing(): void
    {
        $result = PhoneNumber::safeParse('035 12 345 67');

        self::assertFalse($result->success);
        self::assertNull($result->data);
        self::assertSame(ErrorCode::UnknownOperator, $result->error?->errorCode);
    }

    public function testSafeParseRejectsNonStringsAsInvalidType(): void
    {
        self::assertSame(ErrorCode::InvalidType, PhoneNumber::safeParse(self::NUMBER_NOT_A_STRING)->error?->errorCode);
    }

    public function testRejectsInvalidUtf8AsInvalidFormat(): void
    {
        $this->expectExceptionObject(new MgUtilsException(ErrorCode::InvalidFormat, 'Phone numbers must be valid UTF-8 text.'));

        PhoneNumber::parse("034\xff12 345 67");
    }

    public function testNeverThrowsAnythingButMgUtilsExceptionForRandomStrings(): void
    {
        mt_srand(self::RANDOM_SEED);
        $alphabet = "0123456789 +-.()_ab\u{a0}\u{ff10}";
        $characters = preg_split('//u', $alphabet, -1, \PREG_SPLIT_NO_EMPTY);
        self::assertIsArray($characters);
        for ($run = 0; $run < self::FUZZ_RUNS; ++$run) {
            $input = '';
            for ($index = mt_rand(0, self::FUZZ_MAX_INPUT_LENGTH); $index > 0; --$index) {
                $input .= $characters[mt_rand(0, \count($characters) - 1)];
            }
            $phone = PhoneNumber::tryParse($input);

            self::assertSame($phone !== null, PhoneNumber::isValid($input));
        }
    }

    /**
     * @return iterable<string, array{PhoneNumberFormat}>
     */
    public static function formats(): iterable
    {
        foreach (PhoneNumberFormat::cases() as $format) {
            yield $format->value => [$format];
        }
    }

    #[DataProvider('formats')]
    public function testReadsBackEveryFormatItWrites(PhoneNumberFormat $format): void
    {
        $phone = PhoneNumber::parse(self::TELMA_NUMBER_WITH_NEW_PREFIX);

        self::assertEquals($phone, PhoneNumber::parse($phone->format($format)));
    }

    public function testNamesEveryOperator(): void
    {
        $names = array_map(static fn(Operator $operator): string => $operator->displayName(), Operator::cases());

        self::assertSame(['Orange Madagascar', 'Airtel Madagascar', 'Telma', 'Blueline'], $names);
    }
}
