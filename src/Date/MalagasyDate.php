<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Date;

use Mdgascar\MdgUtils\Date\Generated\Data;
use Mdgascar\MdgUtils\Date\Internal\DateReader;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\SafeParseResult;

/**
 * A calendar date written in Malagasy.
 *
 * Example:
 *
 *     MalagasyDate::fromString('2026-11-23')->format();                // '23 Novambra 2026'
 *     MalagasyDate::fromString('2026-11-23')->format(DateStyle::Full); // 'Alatsinainy 23 Novambra 2026'
 *     MalagasyDate::monthName(6);                                      // 'Jona'
 *     MalagasyDate::parse('23 Novambra 2026')->month;                  // 11
 */
final readonly class MalagasyDate
{
    private const string ISO_DATE = '/^(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})$/';
    // CLDR pattern letters used by the spec styles; longest first so MMMM wins over MM.
    private const string PATTERN_TOKEN = '/EEEE|MMMM|MMM|MM|dd|d|y/';
    private const string TWO_DIGITS = '%02d';
    private const string ISO_FORMAT = '!Y-m-d';
    private const string ISO_TEXT = '%04d-%02d-%02d';
    private const string ISO_WEEKDAY = 'N';

    private function __construct(
        public int $year,
        public int $month,
        public int $day,
    ) {}

    /**
     * Reads a date written as `YYYY-MM-DD`.
     *
     * Example: `MalagasyDate::fromString('1960-06-26')->month` is `6`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidFormat` for other text or a day that does not exist, `ErrorCode::OutOfRange` outside years 1–9999
     */
    public static function fromString(string $text): self
    {
        if (preg_match(self::ISO_DATE, $text, $match) !== 1) {
            throw new MdgUtilsException(ErrorCode::InvalidFormat, 'Expected a date as YYYY-MM-DD.');
        }

        return self::checked((int) $match['year'], (int) $match['month'], (int) $match['day'], ErrorCode::InvalidFormat);
    }

    /**
     * Reads a date in any style `format()` writes: `23 Novambra 2026`,
     * `Alatsinainy 23 Novambra 2026`, `2026 Nov 23`, `2026-11-23` or `23/11/2026`. Case and spacing
     * do not matter; day and month may have one digit. With `$style`, only that style is read.
     *
     * Example: `MalagasyDate::parse('23/11/2026')->day` is `23`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidFormat` for text in no style, a day that
     *                          does not exist or a wrong weekday, `ErrorCode::OutOfRange` outside
     *                          years 1–9999, `ErrorCode::Ambiguous` when two styles read different
     *                          dates (only for years up to 31, such as `5 Mey 12`; pass the style)
     */
    public static function parse(string $text, ?DateStyle $style = null): self
    {
        $result = self::read($text, $style);
        if ($result instanceof MdgUtilsException) {
            throw $result;
        }

        return $result;
    }

    /**
     * Like `parse()`, but never throws.
     *
     * @return SafeParseResult<self>
     */
    public static function safeParse(mixed $text, ?DateStyle $style = null): SafeParseResult
    {
        if (!\is_string($text)) {
            return SafeParseResult::failure(new MdgUtilsException(ErrorCode::InvalidType, 'Expected the date as a string.'));
        }
        $result = self::read($text, $style);

        return $result instanceof MdgUtilsException ? SafeParseResult::failure($result) : SafeParseResult::success($result);
    }

    /**
     * Like `parse()`, but returns `null` instead of throwing.
     *
     * Example: `MalagasyDate::tryParse('30/02/2026')` returns `null`.
     */
    public static function tryParse(mixed $text, ?DateStyle $style = null): ?self
    {
        return self::safeParse($text, $style)->data;
    }

    /**
     * A date from its calendar fields.
     *
     * Example: `MalagasyDate::fromParts(1960, 6, 26)->format(DateStyle::Full)` returns `'Alahady 26 Jona 1960'`.
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` for a day that does not exist or a year outside 1–9999
     */
    public static function fromParts(int $year, int $month, int $day): self
    {
        return self::checked($year, $month, $day, ErrorCode::OutOfRange);
    }

    /**
     * The calendar date of a PHP date, in its own time zone.
     *
     * Example: `MalagasyDate::fromDateTime(new DateTimeImmutable('2026-11-23'))->day` is `23`.
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` outside years 1–9999
     */
    public static function fromDateTime(\DateTimeInterface $date): self
    {
        return self::fromParts((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));
    }

    /**
     * The Malagasy name of a month, 1 (Janoary) to 12 (Desambra).
     *
     * Example: `MalagasyDate::monthName(11, NameWidth::Abbreviated)` returns `'Nov'`.
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` outside 1–12
     */
    public static function monthName(int $month, NameWidth $width = NameWidth::Wide): string
    {
        $names = $width === NameWidth::Wide ? Data::MONTH_NAMES : Data::MONTH_ABBREVIATIONS;

        return $names[self::assertNumber($month, \count(Data::MONTH_NAMES) - 1, 'Month')];
    }

    /**
     * The Malagasy name of a weekday, numbered as in ISO 8601 (Monday 1 … Sunday 7).
     *
     * Example: `MalagasyDate::weekdayName(1)` returns `'Alatsinainy'`.
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` outside 1–7
     */
    public static function weekdayName(int $isoWeekday, NameWidth $width = NameWidth::Wide): string
    {
        $names = $width === NameWidth::Wide ? Data::WEEKDAY_NAMES : Data::WEEKDAY_ABBREVIATIONS;

        return $names[self::assertNumber($isoWeekday, \count(Data::WEEKDAY_NAMES) - 1, 'Weekday')];
    }

    /**
     * Writes the date in Malagasy; `DateStyle::Long` by default.
     *
     * Example: `MalagasyDate::fromString('2026-11-23')->format(DateStyle::Numeric)` returns `'23/11/2026'`.
     */
    public function format(DateStyle $style = DateStyle::DEFAULT): string
    {
        $values = [
            'EEEE' => self::weekdayName($this->isoWeekday()),
            'MMMM' => self::monthName($this->month),
            'MMM' => self::monthName($this->month, NameWidth::Abbreviated),
            'MM' => \sprintf(self::TWO_DIGITS, $this->month),
            'dd' => \sprintf(self::TWO_DIGITS, $this->day),
            'd' => (string) $this->day,
            'y' => (string) $this->year,
        ];
        $pattern = $style->pattern();

        return (string) preg_replace_callback(self::PATTERN_TOKEN, static fn(array $token): string => $values[$token[0]], $pattern);
    }

    /**
     * ISO 8601 weekday: Monday 1 … Sunday 7.
     *
     * Example: `MalagasyDate::fromString('2026-11-23')->isoWeekday()` returns `1`.
     */
    public function isoWeekday(): int
    {
        $text = \sprintf(self::ISO_TEXT, $this->year, $this->month, $this->day);
        $date = \DateTimeImmutable::createFromFormat(self::ISO_FORMAT, $text, new \DateTimeZone('UTC'));

        return $date === false ? 0 : (int) $date->format(self::ISO_WEEKDAY);
    }

    private static function read(string $text, ?DateStyle $style): self|MdgUtilsException
    {
        if (mb_strlen($text) > Data::MAX_READ_LENGTH) {
            return new MdgUtilsException(ErrorCode::InvalidFormat, \sprintf('Dates are at most %d characters.', Data::MAX_READ_LENGTH));
        }
        $trimmed = trim($text, " \t\n\r\0\x0B\u{00A0}");
        $dates = [];
        $failure = null;
        foreach ($style === null ? DateStyle::cases() : [$style] as $candidate) {
            $fields = DateReader::read($trimmed, $candidate);
            if ($fields === null) {
                continue;
            }
            $date = self::fromFields($fields);
            if ($date instanceof MdgUtilsException) {
                $failure ??= $date;
            } else {
                $dates[$date->format(DateStyle::Short)] = $date;
            }
        }

        return self::oneDate($dates, $failure);
    }

    /**
     * A text two styles read as different dates ('5 Mey 12': May is 'Mey' in both) is ambiguous.
     *
     * @param array<string, self> $dates
     */
    private static function oneDate(array $dates, ?MdgUtilsException $failure): self|MdgUtilsException
    {
        if (\count($dates) > 1) {
            return new MdgUtilsException(ErrorCode::Ambiguous, 'This text reads as more than one date; pass the style.');
        }

        return array_values($dates)[0] ?? $failure ?? new MdgUtilsException(ErrorCode::InvalidFormat, 'Expected a date such as 23 Novambra 2026 or 23/11/2026.');
    }

    /**
     * The calendar checks the fields; a weekday, when written, must be the date's own.
     *
     * @param array{year: int, month: int, day: int, weekday: int|null} $fields
     */
    private static function fromFields(array $fields): self|MdgUtilsException
    {
        try {
            $date = self::checked($fields['year'], $fields['month'], $fields['day'], ErrorCode::InvalidFormat);
        } catch (MdgUtilsException $exception) {
            return $exception;
        }
        if ($fields['weekday'] !== null && $fields['weekday'] !== $date->isoWeekday()) {
            return new MdgUtilsException(ErrorCode::InvalidFormat, 'The weekday does not match the date.');
        }

        return $date;
    }

    private static function checked(int $year, int $month, int $day, ErrorCode $impossibleDate): self
    {
        if ($year < Data::MIN_YEAR || $year > Data::MAX_YEAR) {
            throw new MdgUtilsException(ErrorCode::OutOfRange, \sprintf('Years go from %d to %d.', Data::MIN_YEAR, Data::MAX_YEAR));
        }
        if (!checkdate($month, $day, $year)) {
            throw new MdgUtilsException($impossibleDate, 'This day does not exist.');
        }

        return new self($year, $month, $day);
    }

    private static function assertNumber(int $value, int $last, string $what): int
    {
        if ($value < 1 || $value > $last) {
            throw new MdgUtilsException(ErrorCode::OutOfRange, \sprintf('%s numbers go from 1 to %d.', $what, $last));
        }

        return $value;
    }
}
