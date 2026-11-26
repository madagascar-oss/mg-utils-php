<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Phone;

use Madagascar\MgUtils\ErrorCode;
use Madagascar\MgUtils\MgUtilsException;
use Madagascar\MgUtils\Phone\Generated\Data;
use Madagascar\MgUtils\SafeParseResult;

/**
 * A Malagasy mobile number that passed validation.
 *
 * Example:
 *
 *     $phone = PhoneNumber::parse('034 12 345 67');
 *     $phone->e164;                                    // '+261341234567'
 *     $phone->operator;                                // Operator::Telma
 *     $phone->format(PhoneNumberFormat::International); // '+261 34 12 345 67'
 */
final readonly class PhoneNumber
{
    // Bounded so a parser that reads user input never scans unbounded text.
    private const int MAX_INPUT_LENGTH = 32;
    // Spaces (including Unicode spaces such as NBSP), dashes, dots and parentheses.
    private const string SEPARATORS = '/[\s\p{Zs}().-]/u';
    // A national significant number never starts with the trunk prefix 0 (ITU-T E.164).
    private const string NATIONAL_NUMBER_DIGITS = '/^[1-9][0-9]*$/';

    private function __construct(
        public string $e164,
        public string $nationalNumber,
        public Operator $operator,
    ) {}

    /**
     * Reads a Malagasy mobile number written in any common way.
     *
     * Accepts `034 12 345 67`, `+261 34 12 345 67`, `00261…`, `261…` and `34 12 345 67`.
     * Example: `PhoneNumber::parse('+261 34 12 345 67')->nationalNumber` is `'341234567'`.
     *
     * @throws MgUtilsException with `ErrorCode::InvalidFormat` or `ErrorCode::UnknownOperator`
     */
    public static function parse(string $input): self
    {
        $result = self::read($input);
        if ($result instanceof MgUtilsException) {
            throw $result;
        }

        return $result;
    }

    /**
     * Like `parse()`, but returns `null` instead of throwing.
     *
     * Example: `PhoneNumber::tryParse('035 12 345 67')` returns `null`.
     */
    public static function tryParse(mixed $input): ?self
    {
        return self::safeParse($input)->data;
    }

    /**
     * Like `parse()`, but never throws: returns the number or the reason it was rejected.
     *
     * Example: `PhoneNumber::safeParse('035 12 345 67')->error?->errorCode` is
     * `ErrorCode::UnknownOperator`.
     *
     * @return SafeParseResult<self>
     */
    public static function safeParse(mixed $input): SafeParseResult
    {
        if (!\is_string($input)) {
            return SafeParseResult::failure(new MgUtilsException(ErrorCode::InvalidType, 'Expected the phone number as a string.'));
        }
        $result = self::read($input);

        return $result instanceof self ? SafeParseResult::success($result) : SafeParseResult::failure($result);
    }

    /**
     * Tells whether the input is a valid Malagasy mobile number. Never throws.
     *
     * Example: `PhoneNumber::isValid('034 12 345 67')` returns `true`.
     */
    public static function isValid(mixed $input): bool
    {
        return \is_string($input) && self::read($input) instanceof self;
    }

    /**
     * Writes the number in a standard form.
     *
     * Example: `PhoneNumber::parse('+261341234567')->format(PhoneNumberFormat::National)`
     * returns `'034 12 345 67'`.
     */
    public function format(PhoneNumberFormat $format): string
    {
        return match ($format) {
            PhoneNumberFormat::E164 => $this->e164,
            PhoneNumberFormat::International => '+' . Data::COUNTRY_CODE . ' ' . self::groupDigits($this->nationalNumber),
            PhoneNumberFormat::National => Data::TRUNK_PREFIX . self::groupDigits($this->nationalNumber),
        };
    }

    private static function read(string $input): self|MgUtilsException
    {
        $nationalNumber = self::readNationalNumber($input);
        if ($nationalNumber instanceof MgUtilsException) {
            return $nationalNumber;
        }
        $operatorId = Data::OPERATOR_BY_PREFIX[substr($nationalNumber, 0, Data::OPERATOR_PREFIX_LENGTH)] ?? null;
        if ($operatorId === null) {
            return new MgUtilsException(ErrorCode::UnknownOperator, 'The prefix is not assigned to a Malagasy mobile operator.');
        }

        return new self('+' . Data::COUNTRY_CODE . $nationalNumber, $nationalNumber, Operator::from($operatorId));
    }

    private static function readNationalNumber(string $input): string|MgUtilsException
    {
        $length = preg_match_all('/./su', $input);
        if ($length === false) {
            return new MgUtilsException(ErrorCode::InvalidFormat, 'Phone numbers must be valid UTF-8 text.');
        }
        if ($length > self::MAX_INPUT_LENGTH) {
            return new MgUtilsException(ErrorCode::InvalidFormat, \sprintf('Phone numbers are at most %d characters.', self::MAX_INPUT_LENGTH));
        }
        $nationalNumber = self::stripNationalPrefix((string) preg_replace(self::SEPARATORS, '', $input));
        if ($nationalNumber === null || preg_match(self::NATIONAL_NUMBER_DIGITS, $nationalNumber) !== 1) {
            return new MgUtilsException(ErrorCode::InvalidFormat, 'Expected a Malagasy number such as 034 12 345 67.');
        }

        return $nationalNumber;
    }

    private static function stripNationalPrefix(string $compact): ?string
    {
        $prefixes = ['+' . Data::COUNTRY_CODE, Data::INTERNATIONAL_PREFIX . Data::COUNTRY_CODE, Data::COUNTRY_CODE, Data::TRUNK_PREFIX, ''];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($compact, $prefix) && \strlen($compact) === \strlen($prefix) + Data::NATIONAL_NUMBER_LENGTH) {
                return substr($compact, \strlen($prefix));
            }
        }

        return null;
    }

    private static function groupDigits(string $nationalNumber): string
    {
        $groups = [];
        $start = 0;
        foreach (Data::GROUPING as $size) {
            $groups[] = substr($nationalNumber, $start, $size);
            $start += $size;
        }

        return implode(' ', $groups);
    }
}
