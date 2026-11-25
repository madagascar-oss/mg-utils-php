<?php

declare(strict_types=1);

namespace Madagascar\MgUtils\Date;

use Madagascar\MgUtils\Date\Generated\Data;
use Madagascar\MgUtils\ErrorCode;
use Madagascar\MgUtils\MgUtilsException;

/**
 * A calendar date written in Malagasy.
 *
 * Example:
 *
 *     MalagasyDate::fromString('2026-11-23')->format();                // '23 Novambra 2026'
 *     MalagasyDate::fromString('2026-11-23')->format(DateStyle::Full); // 'Alatsinainy 23 Novambra 2026'
 *     MalagasyDate::monthName(6);                                      // 'Jona'
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
     * @throws MgUtilsException with `ErrorCode::InvalidFormat` for other text or a day that does not exist, `ErrorCode::OutOfRange` outside years 1–9999
     */
    public static function fromString(string $text): self
    {
        if (preg_match(self::ISO_DATE, $text, $match) !== 1) {
            throw new MgUtilsException(ErrorCode::InvalidFormat, 'Expected a date as YYYY-MM-DD.');
        }

        return self::checked((int) $match['year'], (int) $match['month'], (int) $match['day'], ErrorCode::InvalidFormat);
    }

    /**
     * A date from its calendar fields.
     *
     * Example: `MalagasyDate::fromParts(1960, 6, 26)->format(DateStyle::Full)` returns `'Alahady 26 Jona 1960'`.
     *
     * @throws MgUtilsException with `ErrorCode::OutOfRange` for a day that does not exist or a year outside 1–9999
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
     * @throws MgUtilsException with `ErrorCode::OutOfRange` outside years 1–9999
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
     * @throws MgUtilsException with `ErrorCode::OutOfRange` outside 1–12
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
     * @throws MgUtilsException with `ErrorCode::OutOfRange` outside 1–7
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
    public function format(?DateStyle $style = null): string
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
        $pattern = ($style ?? DateStyle::cases()[0])->pattern();

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

    private static function checked(int $year, int $month, int $day, ErrorCode $impossibleDate): self
    {
        if ($year < Data::MIN_YEAR || $year > Data::MAX_YEAR) {
            throw new MgUtilsException(ErrorCode::OutOfRange, \sprintf('Years go from %d to %d.', Data::MIN_YEAR, Data::MAX_YEAR));
        }
        if (!checkdate($month, $day, $year)) {
            throw new MgUtilsException($impossibleDate, 'This day does not exist.');
        }

        return new self($year, $month, $day);
    }

    private static function assertNumber(int $value, int $last, string $what): int
    {
        if ($value < 1 || $value > $last) {
            throw new MgUtilsException(ErrorCode::OutOfRange, \sprintf('%s numbers go from 1 to %d.', $what, $last));
        }

        return $value;
    }
}
