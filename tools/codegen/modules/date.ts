// date: spec data → lookup tables → PHP files.
import type { PhpOutput } from "./shared.ts";
import {
  dataClass,
  pascalCase,
  phpFile,
  phpLiteral,
  ROOT_NAMESPACE,
} from "../emit.ts";

interface DateData {
  readonly months: readonly {
    readonly number: number;
    readonly name: string;
    readonly abbreviation: string;
  }[];
  readonly weekdays: readonly {
    readonly isoNumber: number;
    readonly name: string;
    readonly abbreviation: string;
  }[];
  readonly styles: readonly { readonly id: string; readonly pattern: string }[];
  readonly time: {
    readonly prefix: string;
    readonly after: string;
    readonly before: string;
    readonly more: string;
  };
  readonly fractions: readonly { readonly minutes: number; readonly word: string }[];
  readonly dayParts: readonly {
    readonly from: number;
    readonly to: number;
    readonly word: string;
  }[];
  readonly limits: {
    readonly minYear: number;
    readonly maxYear: number;
    readonly maxReadLength: number;
    readonly maxTimeReadLength: number;
  };
}

interface DateTables {
  /** Index = month number (1–12); index 0 is ''. */
  readonly monthNames: readonly string[];
  readonly monthAbbreviations: readonly string[];
  /** Index = ISO weekday (Monday 1 … Sunday 7); index 0 is ''. */
  readonly weekdayNames: readonly string[];
  readonly weekdayAbbreviations: readonly string[];
  /** Style id → CLDR pattern; the first entry is the default. */
  readonly styles: readonly (readonly [string, string])[];
  readonly minYear: number;
  readonly maxYear: number;
  readonly maxTimeReadLength: number;
  readonly time: DateData["time"];
  readonly fractions: Record<number, string>;
  readonly dayParts: readonly string[];
  readonly maxReadLength: number;
}

function byNumber<T>(
  entries: readonly T[],
  numberOf: (entry: T) => number,
  pick: (entry: T) => string,
): string[] {
  const sorted = [...entries].sort(
    (left, right) => numberOf(left) - numberOf(right),
  );
  sorted.forEach((entry, index) => {
    if (numberOf(entry) !== index + 1)
      throw new Error(`spec gap at number ${String(index + 1)}`);
  });
  return ["", ...sorted.map(pick)];
}

function dateTables(data: DateData): DateTables {
  const monthNumber = (month: DateData["months"][number]): number =>
    month.number;
  const weekdayNumber = (weekday: DateData["weekdays"][number]): number =>
    weekday.isoNumber;
  return {
    monthNames: byNumber(data.months, monthNumber, (month) => month.name),
    monthAbbreviations: byNumber(
      data.months,
      monthNumber,
      (month) => month.abbreviation,
    ),
    weekdayNames: byNumber(
      data.weekdays,
      weekdayNumber,
      (weekday) => weekday.name,
    ),
    weekdayAbbreviations: byNumber(
      data.weekdays,
      weekdayNumber,
      (weekday) => weekday.abbreviation,
    ),
    styles: data.styles.map(({ id, pattern }) => [id, pattern] as const),
    minYear: data.limits.minYear,
    maxYear: data.limits.maxYear,
    maxReadLength: data.limits.maxReadLength,
    maxTimeReadLength: data.limits.maxTimeReadLength,
    time: data.time,
    fractions: Object.fromEntries(
      data.fractions.map(({ minutes, word }) => [minutes, word]),
    ),
    dayParts: dayPartByHour(data.dayParts),
  };
}

function dateStyleEnum(styles: readonly (readonly [string, string])[]): string {
  const cases = styles
    .map(([id]) => `    case ${pascalCase(id)} = ${phpLiteral(id)};`)
    .join("\n");
  const patterns = styles
    .map(
      ([id, pattern]) =>
        `            self::${pascalCase(id)} => ${phpLiteral(pattern)},`,
    )
    .join("\n");
  return phpFile(
    `${ROOT_NAMESPACE}\\Date`,
    `/**
 * How \`MalagasyDate::format()\` writes a date.
 *
 * Example: \`MalagasyDate::fromString('2026-11-23')->format(DateStyle::Full)\` returns
 * \`'Alatsinainy 23 Novambra 2026'\`.
 */
enum DateStyle: string
{
${cases}

    /** The style used when none is given: the first one in the spec. */
    public const self DEFAULT = self::${pascalCase(styles[0]?.[0] ?? "")};

    /**
     * The CLDR pattern of this style, e.g. \`d MMMM y\` for \`DateStyle::Long\`.
     */
    public function pattern(): string
    {
        return match ($this) {
${patterns}
        };
    }
}`,
  );
}

const HOURS_PER_DAY = 24;

/** The part of the day of each hour 0–23, checking the ranges cover every hour exactly once. */
function dayPartByHour(parts: DateData["dayParts"]): string[] {
  return Array.from({ length: HOURS_PER_DAY }, (_, hour) => {
    const matches = parts.filter(({ from, to }) => hour >= from && hour <= to);
    if (matches.length !== 1)
      throw new Error(`day parts: hour ${String(hour)} is not covered once`);
    return matches[0]?.word ?? "";
  });
}

export function emit(data: DateData, source: string): PhpOutput[] {
  const tables = dateTables(data);
  return [
    {
      path: "Date/Generated/Data.php",
      source: dataClass(
        `${ROOT_NAMESPACE}\\Date\\Generated`,
        [
          {
            name: "MONTH_NAMES",
            type: "array",
            value: tables.monthNames,
            varDoc: "list<string>",
          },
          {
            name: "MONTH_ABBREVIATIONS",
            type: "array",
            value: tables.monthAbbreviations,
            varDoc: "list<string>",
          },
          {
            name: "WEEKDAY_NAMES",
            type: "array",
            value: tables.weekdayNames,
            varDoc: "list<string>",
          },
          {
            name: "WEEKDAY_ABBREVIATIONS",
            type: "array",
            value: tables.weekdayAbbreviations,
            varDoc: "list<string>",
          },
          { name: "MIN_YEAR", type: "int", value: tables.minYear },
          { name: "MAX_YEAR", type: "int", value: tables.maxYear },
          { name: "MAX_READ_LENGTH", type: "int", value: tables.maxReadLength },
          {
            name: "MAX_TIME_READ_LENGTH",
            type: "int",
            value: tables.maxTimeReadLength,
          },
          { name: "TIME_PREFIX", type: "string", value: tables.time.prefix },
          { name: "TIME_AFTER", type: "string", value: tables.time.after },
          { name: "TIME_BEFORE", type: "string", value: tables.time.before },
          { name: "TIME_MORE", type: "string", value: tables.time.more },
          {
            name: "TIME_FRACTIONS",
            type: "array",
            value: tables.fractions,
            varDoc: "array<int, string>",
          },
          {
            name: "DAY_PARTS",
            type: "array",
            value: tables.dayParts,
            varDoc: "list<string>",
          },
        ],
        source,
      ),
    },
    { path: "Date/DateStyle.php", source: dateStyleEnum(tables.styles) },
  ];
}
