<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Words;

use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\ExactNumber;
use Mdgascar\MdgUtils\Internal\DecimalText;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\SafeParseResult;
use Mdgascar\MdgUtils\Words\Generated\Data;
use Mdgascar\MdgUtils\Words\Internal\Cardinal;
use Mdgascar\MdgUtils\Words\Internal\WordsReader;

/**
 * Malagasy number words: cardinals, ordinals, and words read back into numbers.
 */
final class NumberWords
{
    private const string ONE = '1';
    private const string ZERO_DIGIT = '0';
    private const string DECIMAL_POINT = '.';
    // Rule 13: ASCII digits, then optionally a point and more digits. Nothing else.
    private const string DECIMAL_TEXT = '/^(?<whole>[0-9]+)(?:\.(?<decimals>[0-9]+))?$/D';
    private const string DIGIT_TEXT = '/^[0-9]+$/D';
    private const string SPACES = '/[\s\p{Zs}]+/u';
    private const string APOSTROPHES = "/[\u{2018}\u{2019}\u{02BC}`\u{00B4}]/u";
    // Older spellings of "ambin'ny" (dictionary: "ambin' ny"; Assimil: "amby ny").
    private const string SPLIT_AMBINNY = "/\\bambin' ny\\b|\\bamby ny\\b/u";

    /**
     * Spells a number in Malagasy words, smallest part first, with `faingo` before the decimals.
     *
     * - An int is read whole, up to `PHP_INT_MAX`.
     * - A float is read with the decimals of its shortest exact form (`1.0` has none), up to
     *   9 007 199 254 740 991; above about 15 significant digits a float has already lost
     *   digits, so pass text instead.
     * - Text is plain decimal digits (`'2000000000000000.9'`), read exactly, at most 100
     *   characters (spec words rule 13).
     *
     * Examples: `NumberWords::spell(2026)` returns `'enina amby roapolo sy roa arivo'`,
     * `NumberWords::spell(3.14)` returns `"telo faingo efatra ambin'ny folo"`,
     * `NumberWords::spell(30_000_000, NumberSystem::Traditional)` returns `'telo safatsiroa'`.
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` below 0 or above a float's exact
     *                          range, `ErrorCode::InvalidType` for NAN or INF, or
     *                          `ErrorCode::InvalidFormat` for text that is not plain decimal
     */
    public static function spell(int|float|string $number, NumberSystem $system = NumberSystem::Standard): string
    {
        return match (true) {
            \is_int($number) => self::spellInt($number, $system),
            \is_float($number) => self::spellFloat($number, $system),
            default => self::spellText($number, $system),
        };
    }

    private static function spellInt(int $value, NumberSystem $system): string
    {
        if ($value < Data::MIN_VALUE) {
            throw self::outOfRange(PHP_INT_MAX);
        }

        return self::words((string) $value, null, $system);
    }

    private static function spellText(string $text, NumberSystem $system): string
    {
        if (\strlen($text) > Data::MAX_TEXT_LENGTH || preg_match(self::DECIMAL_TEXT, $text, $match) !== 1) {
            throw new MdgUtilsException(
                ErrorCode::InvalidFormat,
                \sprintf('NumberWords reads digits with an optional .decimals, at most %d characters.', Data::MAX_TEXT_LENGTH),
            );
        }
        $decimals = rtrim($match['decimals'] ?? '', self::ZERO_DIGIT);

        return self::words($match['whole'], $decimals === '' ? null : $decimals, $system);
    }

    private static function spellFloat(float $value, NumberSystem $system): string
    {
        if (!is_finite($value)) {
            throw new MdgUtilsException(ErrorCode::InvalidType, 'NumberWords expects a finite number.');
        }
        if ($value < Data::MIN_VALUE || $value > DecimalText::MAX_EXACT_FLOAT_INTEGER) {
            throw self::outOfRange(DecimalText::MAX_EXACT_FLOAT_INTEGER);
        }
        $parts = explode(self::DECIMAL_POINT, DecimalText::plain($value));

        return self::words($parts[0], $parts[1] ?? null, $system);
    }

    // The whole digits, then "faingo" and the decimals when there are any.
    private static function words(string $whole, ?string $decimals, NumberSystem $system): string
    {
        return Cardinal::parts($whole, $decimals, $system);
    }

    private static function outOfRange(int $max): MdgUtilsException
    {
        return new MdgUtilsException(
            ErrorCode::OutOfRange,
            \sprintf('NumberWords supports %d to %d.', Data::MIN_VALUE, $max),
        );
    }

    /**
     * The Malagasy ordinal of a whole number: `faha` before the cardinal words, `fah` before a
     * vowel, and `voalohany` for 1 (spec words rule 14).
     *
     * Examples: `NumberWords::spellOrdinal(3)` returns `'fahatelo'`, `spellOrdinal(4)` returns
     * `'fahefatra'`, `spellOrdinal(1)` returns `'voalohany'`.
     *
     * @param int|string $number a whole number from 1, or digit text read exactly
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` below 1, or
     *                          `ErrorCode::InvalidFormat` for text that is not digits
     */
    public static function spellOrdinal(int|string $number, NumberSystem $system = NumberSystem::Standard): string
    {
        if (\is_string($number) && (\strlen($number) > Data::MAX_TEXT_LENGTH || preg_match(self::DIGIT_TEXT, $number) !== 1)) {
            throw new MdgUtilsException(
                ErrorCode::InvalidFormat,
                \sprintf('spellOrdinal reads whole digits, at most %d characters.', Data::MAX_TEXT_LENGTH),
            );
        }
        $digits = ltrim((string) $number, self::ZERO_DIGIT);
        if ($digits === '' || $digits[0] === '-') {
            throw new MdgUtilsException(ErrorCode::OutOfRange, 'Ordinals start at 1 (voalohany).');
        }
        if ($digits === self::ONE) {
            return Data::ORDINAL_FIRST;
        }
        $cardinal = Cardinal::whole($digits, $system);
        $prefix = \in_array($cardinal[0], Data::VOWELS, true) ? Data::ORDINAL_PREFIX_BEFORE_VOWEL : Data::ORDINAL_PREFIX;

        return $prefix . $cardinal;
    }

    /**
     * Reads Malagasy number words back into the number that `spell()` writes as them, in either
     * system (spec words rule 15). Case, spacing, curly apostrophes and the older `ambin' ny`
     * and `amby ny` do not matter.
     *
     * Example: `NumberWords::parse('roa arivo')->value()` returns `2000`;
     * `NumberWords::parse('folo lavitrisa lavitrisa')->digits` is `'10000000000000000000'`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidFormat` for words `spell()` does not
     *                          write, `ErrorCode::Ambiguous` for the rare words two numbers share
     */
    public static function parse(string $text): ExactNumber
    {
        $result = self::read($text);
        if ($result instanceof MdgUtilsException) {
            throw $result;
        }

        return $result;
    }

    /**
     * Like `parse()`, but never throws.
     *
     * @return SafeParseResult<ExactNumber>
     */
    public static function safeParse(mixed $text): SafeParseResult
    {
        if (!\is_string($text)) {
            return SafeParseResult::failure(new MdgUtilsException(ErrorCode::InvalidType, 'Expected the words as a string.'));
        }
        $result = self::read($text);

        return $result instanceof MdgUtilsException ? SafeParseResult::failure($result) : SafeParseResult::success($result);
    }

    /**
     * Like `parse()`, but returns `null` instead of throwing.
     */
    public static function tryParse(mixed $text): ?ExactNumber
    {
        return self::safeParse($text)->data;
    }

    private static function read(string $text): ExactNumber|MdgUtilsException
    {
        if (\strlen($text) > Data::MAX_WORDS_LENGTH) {
            return new MdgUtilsException(ErrorCode::InvalidFormat, \sprintf('Words are at most %d characters.', Data::MAX_WORDS_LENGTH));
        }
        $words = self::normalise($text);
        $readings = self::spelledReadings($words);

        return match (\count($readings)) {
            0 => new MdgUtilsException(ErrorCode::InvalidFormat, 'Expected Malagasy number words, as spell() writes them.'),
            1 => ExactNumber::fromDigits($readings[0]),
            default => new MdgUtilsException(ErrorCode::Ambiguous, 'These words read as ' . implode(' and as ', $readings) . '.'),
        };
    }

    // Case, apostrophes, spacing and older spellings do not change the number.
    private static function normalise(string $text): string
    {
        $words = mb_strtolower(trim((string) preg_replace(self::SPACES, ' ', $text)));
        $words = (string) preg_replace(self::APOSTROPHES, "'", $words);

        return (string) preg_replace(self::SPLIT_AMBINNY, Data::JOINER_UNITS_BEFORE_TEN, $words);
    }

    /**
     * Every exact value whose spelling is these words: one, none, or (rarely) more.
     *
     * @return list<string>
     */
    private static function spelledReadings(string $words): array
    {
        $read = WordsReader::read($words);
        $texts = [];
        foreach ($read['wholes'] as $whole) {
            foreach ($read['decimals'] ?? [''] as $decimals) {
                $texts[] = DecimalText::canonical($decimals === '' ? $whole : $whole . self::DECIMAL_POINT . $decimals);
            }
        }

        return array_values(array_filter(array_unique($texts), static function (string $text) use ($words): bool {
            $parts = explode(self::DECIMAL_POINT, $text);
            foreach (NumberSystem::cases() as $system) {
                if (Cardinal::parts($parts[0], $parts[1] ?? null, $system) === $words) {
                    return true;
                }
            }

            return false;
        }));
    }
}
