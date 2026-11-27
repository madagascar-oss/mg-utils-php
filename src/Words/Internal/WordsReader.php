<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Words\Internal;

use Mdgascar\MdgUtils\Internal\DecimalText;
use Mdgascar\MdgUtils\Words\Generated\Data;
use Mdgascar\MdgUtils\Words\NumberSystem;

/**
 * Malagasy number words → every exact value they can stand for, as digit text. `NumberWords`
 * keeps the values that spell back to the same words; more than one left means the words are
 * ambiguous. Values are digit text, so every size stays exact.
 *
 * @internal not part of the public API; use `NumberWords::parse()`
 */
final class WordsReader
{
    private const int BASE = 10;
    private const string ZERO = '0';
    // A phrase alternates a word and a joiner: word, joiner, word, ….
    private const int TOKENS_PER_STEP = 2;
    // The longest phrase below 1000: three words and two joiners.
    private const int MAX_PHRASE_TOKENS = 5;
    // Readings kept per part: real words have one or two; more only in made-up text.
    private const int MAX_READINGS = 8;
    // The whole part and, at most, the decimals.
    private const int MAX_PARTS = 2;
    private const string LARGEST_DIGIT = '9';

    /** @var array<string, list<string>> readings of each span, by "start:end" */
    private array $spans = [];

    /**
     * @param list<string> $tokens
     */
    private function __construct(
        private readonly array $tokens,
    ) {}

    /**
     * Every value normalised words can stand for, before checking which ones spell back to them.
     *
     * @return array{wholes: list<string>, decimals: list<string>|null} digit texts; decimals
     *                                                                   is null without "faingo"
     */
    public static function read(string $words): array
    {
        $parts = self::splitOn(explode(' ', $words), Data::DECIMAL_SEPARATOR);
        if (\count($parts) > self::MAX_PARTS) {
            return ['wholes' => [], 'decimals' => null];
        }

        return [
            'wholes' => self::wholeValues($parts[0]),
            'decimals' => isset($parts[1]) ? self::decimalValues($parts[1]) : null,
        ];
    }

    /**
     * A whole number: groups joined by "sy", or "aotra".
     *
     * @param list<string> $tokens
     *
     * @return list<string>
     */
    private static function wholeValues(array $tokens): array
    {
        if ($tokens === [Data::ZERO_WORD]) {
            return [self::ZERO];
        }
        $totals = [self::ZERO];
        foreach (self::splitOn($tokens, Data::JOINER_BETWEEN_GROUPS) as $group) {
            $sums = [];
            foreach ($totals as $total) {
                foreach (self::groupValues($group) as $value) {
                    $sums[] = DecimalText::add($total, $value);
                }
            }
            $totals = self::limited($sums);
        }

        return $totals;
    }

    /**
     * After "faingo": one "aotra" per leading zero, then a whole number.
     *
     * @param list<string> $tokens
     *
     * @return list<string>
     */
    private static function decimalValues(array $tokens): array
    {
        $zeros = 0;
        while (($tokens[$zeros] ?? null) === Data::ZERO_WORD) {
            ++$zeros;
        }
        if ($zeros === \count($tokens)) {
            return [];
        }
        $prefix = str_repeat(self::ZERO, $zeros);

        return array_map(static fn(string $value): string => $prefix . $value, self::wholeValues(\array_slice($tokens, $zeros)));
    }

    /**
     * One group of a whole number: a count and a group word ("roa arivo"), or a phrase.
     *
     * @param list<string> $tokens
     *
     * @return list<string>
     */
    private static function groupValues(array $tokens): array
    {
        $last = \count($tokens) - 1;
        $exponent = self::exponentOf($tokens[$last] ?? '');
        if ($exponent === null) {
            $phrase = self::phrase($tokens);

            return $phrase === null ? [] : [$phrase];
        }
        $counts = $last === 0 ? ['1'] : (new self($tokens))->counts(0, $last);

        return array_map(static fn(string $count): string => self::times($count, $exponent), $counts);
    }

    /**
     * Counts written exactly as a count is spelled before a group word.
     *
     * @return list<string>
     */
    private function counts(int $start, int $end): array
    {
        $text = implode(' ', \array_slice($this->tokens, $start, $end - $start));

        return array_values(array_filter(
            $this->values($start, $end),
            static fn(string $count): bool => Cardinal::count($count, NumberSystem::Standard) === $text
                || Cardinal::count($count, NumberSystem::Traditional) === $text,
        ));
    }

    /**
     * Every value of tokens[start, end): a phrase, or parts joined by "amby" ending in a group.
     *
     * @return list<string>
     */
    private function values(int $start, int $end): array
    {
        $key = $start . ':' . $end;
        if (!isset($this->spans[$key])) {
            $phrase = self::phrase(\array_slice($this->tokens, $start, $end - $start));
            $this->spans[$key] = self::limited([...($phrase === null ? [] : [$phrase]), ...$this->grouped($start, $end)]);
        }

        return $this->spans[$key];
    }

    /**
     * "dimy arivo amby roa alina": whatever comes before an "amby", plus a count of the last
     * group. What comes before a group is always smaller than one of that group.
     *
     * @return list<string>
     */
    private function grouped(int $start, int $end): array
    {
        $word = $this->tokens[$end - 1];
        $exponent = self::exponentOf($word);
        if ($exponent === null) {
            return [];
        }
        [$first, $last] = $this->splitRange($start, $end, $word, $exponent);
        $found = [];
        for ($split = $first; $split <= $last; ++$split) {
            if ($split > $start && $this->tokens[$split - 1] !== Data::JOINER_BELOW_THOUSAND) {
                continue;
            }
            $lefts = $split === $start ? [self::ZERO] : $this->values($start, $split - 1);
            $counts = $split === $end - 1 ? ['1'] : $this->counts($split, $end - 1);
            $found = [...$found, ...self::groupSums($lefts, $counts, $exponent)];
        }

        return $found;
    }

    /**
     * Where the count of a group can start: a phrase below 1000 is at most five words, and the
     * part before the group is below one of it, so at most as long as the words of that.
     *
     * @return array{int, int}
     */
    private function splitRange(int $start, int $end, string $word, int $exponent): array
    {
        $hasPhraseCount = self::isPhraseCountGroup($word);
        $first = $hasPhraseCount ? max($start, $end - 1 - self::MAX_PHRASE_TOKENS) : $start;
        $largestLeft = str_repeat(self::LARGEST_DIGIT, $exponent);
        $maxLeftTokens = \count(explode(' ', Cardinal::count($largestLeft, NumberSystem::Standard)));

        return [$first, min($end - 1, $start + $maxLeftTokens + 1)];
    }

    /**
     * @param list<string> $lefts
     * @param list<string> $counts
     *
     * @return list<string>
     */
    private static function groupSums(array $lefts, array $counts, int $exponent): array
    {
        $sums = [];
        foreach ($lefts as $left) {
            if (\strlen($left) > $exponent) {
                continue;
            }
            foreach ($counts as $count) {
                $sums[] = DecimalText::add($left, self::times($count, $exponent));
            }
        }

        return $sums;
    }

    /**
     * "valo amby efapolo sy telonjato" is not one: a phrase alternates words below 1000 and
     * "amby" or "ambin'ny", and its value is their sum.
     *
     * @param list<string> $tokens
     */
    private static function phrase(array $tokens): ?string
    {
        $total = 0;
        foreach ($tokens as $index => $token) {
            $isJoinerPlace = $index % self::TOKENS_PER_STEP === 1;
            $isJoiner = $token === Data::JOINER_BELOW_THOUSAND || $token === Data::JOINER_UNITS_BEFORE_TEN;
            $value = self::phraseWordValue($token);
            if ($isJoinerPlace !== $isJoiner || (!$isJoinerPlace && $value === null)) {
                return null;
            }
            $total += $value ?? 0;
        }

        return \count($tokens) % self::TOKENS_PER_STEP === 1 ? (string) $total : null;
    }

    private static function phraseWordValue(string $word): ?int
    {
        if ($word === Data::UNIT_ONE_IN_COMPOUND) {
            return 1;
        }
        foreach ([Data::UNIT_WORDS, Data::TEN_WORDS, Data::HUNDRED_WORDS] as $place => $words) {
            $digit = array_search($word, $words, true);
            if ($digit !== false && $word !== '') {
                return $digit * self::BASE ** $place;
            }
        }

        return null;
    }

    // The power of ten a group word stands for, in either system.
    private static function exponentOf(string $word): ?int
    {
        foreach ([...Data::TRADITIONAL_SCALES, ...Data::SCALES] as $scale) {
            if ($scale['word'] === $word) {
                return $scale['exponent'];
            }
        }

        return null;
    }

    // Every group but the largest takes a count below 1000; lavitrisa takes any count.
    private static function isPhraseCountGroup(string $word): bool
    {
        foreach (Data::SCALES as $scale) {
            if ($scale['word'] === $word) {
                return $scale['countDigits'] !== null;
            }
        }

        return true;
    }

    private static function times(string $count, int $exponent): string
    {
        return $count . str_repeat(self::ZERO, $exponent);
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private static function limited(array $values): array
    {
        return \array_slice(array_values(array_unique($values)), 0, self::MAX_READINGS);
    }

    /**
     * @param list<string> $tokens
     *
     * @return non-empty-list<list<string>>
     */
    private static function splitOn(array $tokens, string $separator): array
    {
        $parts = [[]];
        foreach ($tokens as $token) {
            if ($token === $separator) {
                $parts[] = [];
            } else {
                $parts[\count($parts) - 1][] = $token;
            }
        }

        return $parts;
    }
}
