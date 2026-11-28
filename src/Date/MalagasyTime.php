<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Date;

use Mdgascar\MdgUtils\Date\Generated\Data;
use Mdgascar\MdgUtils\Date\Internal\TimeTable;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\SafeParseResult;
use Mdgascar\MdgUtils\Words\NumberWords;

/**
 * A time of day said in Malagasy.
 *
 * Example:
 *
 *     MalagasyTime::fromString('14:30')->spell();               // "amin'ny roa sy sasany tolakandro"
 *     MalagasyTime::parse("amin'ny fito maraina")->hour;        // 7
 */
final readonly class MalagasyTime
{
    private const int HOURS_PER_DAY = 24;
    private const int HOURS_ON_THE_CLOCK = 12;
    private const int MINUTES_PER_HOUR = 60;
    // Rule 7: up to a third of an hour the minutes follow the hour with "sy"; around the half
    // hour they count from "sasany"; from 40 they count down to the next hour with "latsaka".
    private const int LAST_MINUTE_AFTER = 20;
    private const int HALF_HOUR = 30;
    private const string TIME_TEXT = '/^(?<hour>[01]?[0-9]|2[0-3]):(?<minute>[0-5][0-9])$/D';
    private const string SPACES = '/[\s\p{Zs}]+/u';
    private const string APOSTROPHES = "/[\u{2018}\u{2019}\u{02BC}`\u{00B4}]/u";

    private function __construct(
        public int $hour,
        public int $minute,
    ) {}

    /**
     * Reads a time written as `HH:MM` (the hour may have one digit).
     *
     * Example: `MalagasyTime::fromString('07:15')->minute` is `15`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidFormat` for other text
     */
    public static function fromString(string $text): self
    {
        if (preg_match(self::TIME_TEXT, $text, $match) !== 1) {
            throw new MdgUtilsException(ErrorCode::InvalidFormat, 'Expected a time as HH:MM.');
        }

        return new self((int) $match['hour'], (int) $match['minute']);
    }

    /**
     * A time from its hour (0–23) and minute (0–59).
     *
     * @throws MdgUtilsException with `ErrorCode::OutOfRange` outside those
     */
    public static function fromParts(int $hour, int $minute): self
    {
        if ($hour < 0 || $hour >= self::HOURS_PER_DAY || $minute < 0 || $minute >= self::MINUTES_PER_HOUR) {
            throw new MdgUtilsException(ErrorCode::OutOfRange, 'Hours go from 0 to 23 and minutes from 0 to 59.');
        }

        return new self($hour, $minute);
    }

    /**
     * The time of a PHP date, in its own time zone.
     */
    public static function fromDateTime(\DateTimeInterface $time): self
    {
        return new self((int) $time->format('G'), (int) $time->format('i'));
    }

    /**
     * Reads a time said in Malagasy, as `spell()` says it, in any case and spacing, with or
     * without `amin'ny`.
     *
     * Example: `MalagasyTime::parse("amin'ny roa sy sasany tolakandro")->hour` is `14`.
     *
     * @throws MdgUtilsException with `ErrorCode::InvalidFormat` for anything else
     */
    public static function parse(string $text): self
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
     * @return SafeParseResult<self>
     */
    public static function safeParse(mixed $text): SafeParseResult
    {
        if (!\is_string($text)) {
            return SafeParseResult::failure(new MdgUtilsException(ErrorCode::InvalidType, 'Expected the time as a string.'));
        }
        $result = self::read($text);

        return $result instanceof MdgUtilsException ? SafeParseResult::failure($result) : SafeParseResult::success($result);
    }

    /**
     * Like `parse()`, but returns `null` instead of throwing.
     */
    public static function tryParse(mixed $text): ?self
    {
        return self::safeParse($text)->data;
    }

    /**
     * The time in Malagasy words: `amin'ny`, the hour on a 12-hour clock, the minutes, then the
     * part of the day.
     *
     * Example: `MalagasyTime::fromString('17:45')->spell()` returns `"amin'ny enina latsaka fahefany hariva"`.
     */
    public function spell(): string
    {
        [$minutes, $hoursLater] = self::minutePhrase($this->minute);
        $named = ($this->hour + $hoursLater) % self::HOURS_PER_DAY;
        $onTheClock = $named % self::HOURS_ON_THE_CLOCK;
        $words = [Data::TIME_PREFIX, NumberWords::spell($onTheClock === 0 ? self::HOURS_ON_THE_CLOCK : $onTheClock), $minutes, Data::DAY_PARTS[$named]];

        return implode(' ', array_filter($words, static fn(string $word): bool => $word !== ''));
    }

    /**
     * The words after the hour, and how many hours later the hour they name is.
     *
     * @return array{string, int}
     */
    private static function minutePhrase(int $minute): array
    {
        $half = Data::TIME_FRACTIONS[self::HALF_HOUR];

        return match (true) {
            $minute === 0 => ['', 0],
            $minute <= self::LAST_MINUTE_AFTER => [Data::TIME_AFTER . ' ' . self::minuteWords($minute), 0],
            $minute < self::HALF_HOUR => [implode(' ', [Data::TIME_AFTER, $half, Data::TIME_BEFORE, self::minuteWords(self::HALF_HOUR - $minute)]), 0],
            $minute === self::HALF_HOUR => [Data::TIME_AFTER . ' ' . $half, 0],
            $minute < self::MINUTES_PER_HOUR - self::LAST_MINUTE_AFTER => [implode(' ', [Data::TIME_AFTER, $half, Data::TIME_MORE, self::minuteWords($minute - self::HALF_HOUR)]), 0],
            default => [Data::TIME_BEFORE . ' ' . self::minuteWords(self::MINUTES_PER_HOUR - $minute), 1],
        };
    }

    // A number of minutes in words: its own word (fahefany, fahatelony, sasany), or counted.
    private static function minuteWords(int $minutes): string
    {
        return Data::TIME_FRACTIONS[$minutes] ?? NumberWords::spell($minutes);
    }

    private static function read(string $text): self|MdgUtilsException
    {
        if (mb_strlen($text) > Data::MAX_TIME_READ_LENGTH) {
            return new MdgUtilsException(ErrorCode::InvalidFormat, \sprintf('Times are at most %d characters.', Data::MAX_TIME_READ_LENGTH));
        }

        return self::byWords()[self::normalise($text)]
            ?? new MdgUtilsException(ErrorCode::InvalidFormat, "Expected a time such as amin'ny roa sy sasany tolakandro.");
    }

    // Case, apostrophes and spacing do not change the time, and "amin'ny" may be left out.
    private static function normalise(string $text): string
    {
        $words = mb_strtolower(trim((string) preg_replace(self::SPACES, ' ', $text)));
        $words = (string) preg_replace(self::APOSTROPHES, "'", $words);

        return str_starts_with($words, Data::TIME_PREFIX . ' ') ? $words : Data::TIME_PREFIX . ' ' . $words;
    }

    /**
     * Every time of the day by its words, built once.
     *
     * @return array<string, self>
     */
    private static function byWords(): array
    {
        return TimeTable::byWords(static function (): array {
            $times = [];
            for ($index = 0; $index < self::HOURS_PER_DAY * self::MINUTES_PER_HOUR; ++$index) {
                $time = new self(intdiv($index, self::MINUTES_PER_HOUR), $index % self::MINUTES_PER_HOUR);
                $times[$time->spell()] = $time;
            }

            return $times;
        });
    }
}
