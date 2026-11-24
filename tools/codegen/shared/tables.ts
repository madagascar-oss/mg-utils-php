// Language-neutral lookup tables built from spec data. Every language target emits these
// same tables, so ports differ only in syntax, never in data.

interface Valued {
  readonly value: number;
  readonly word: string;
}
interface Worded {
  readonly word: string;
}

export interface WordsData {
  readonly zero: Worded;
  readonly units: readonly Valued[];
  readonly unitOneInCompound: Worded;
  readonly tens: readonly Valued[];
  readonly hundreds: readonly Valued[];
  readonly scales: readonly (Valued & { readonly maxCount: number })[];
  readonly joiners: Readonly<Record<'unitsBeforeTen' | 'belowThousand' | 'betweenGroups', Worded>>;
  readonly limits: { readonly min: number; readonly max: number };
}

export interface PhoneData {
  readonly plan: {
    readonly countryCode: string;
    readonly internationalPrefix: string;
    readonly trunkPrefix: string;
    readonly nationalNumberLength: number;
    readonly grouping: readonly number[];
  };
  readonly operators: readonly {
    readonly id: string;
    readonly name: string;
    readonly prefixes: readonly string[];
  }[];
}

export interface WordsTables {
  readonly zeroWord: string;
  readonly unitWords: readonly string[];
  readonly unitOneInCompound: string;
  readonly tenWords: readonly string[];
  readonly hundredWords: readonly string[];
  readonly scales: readonly {
    readonly value: number;
    readonly word: string;
    readonly maxCount: number;
  }[];
  readonly joinerUnitsBeforeTen: string;
  readonly joinerBelowThousand: string;
  readonly joinerBetweenGroups: string;
  readonly minValue: number;
  readonly maxValue: number;
}

export interface PhoneTables {
  readonly countryCode: string;
  readonly internationalPrefix: string;
  readonly trunkPrefix: string;
  readonly nationalNumberLength: number;
  readonly grouping: readonly number[];
  readonly operators: readonly { readonly id: string; readonly name: string }[];
  readonly operatorByPrefix: readonly (readonly [string, string])[];
  readonly operatorPrefixLength: number;
}

// Place values of the digits each word table covers.
const UNIT_STEP = 1;
const TEN_STEP = 10;
const HUNDRED_STEP = 100;

/** Words indexed by digit (index 0 is ''), checked for gaps against the spec values. */
function indexedWords(entries: readonly Valued[], step: number): string[] {
  return ['', ...entries.map((entry) => entry.word)].map((word, digit) => {
    const expected = digit * step;
    const found = entries.find((entry) => entry.value === expected);
    if (digit > 0 && found?.word !== word) throw new Error(`spec gap at value ${String(expected)}`);
    return word;
  });
}

export function wordsTables(data: WordsData): WordsTables {
  return {
    zeroWord: data.zero.word,
    unitWords: indexedWords(data.units, UNIT_STEP),
    unitOneInCompound: data.unitOneInCompound.word,
    tenWords: indexedWords(data.tens, TEN_STEP),
    hundredWords: indexedWords(data.hundreds, HUNDRED_STEP),
    scales: [...data.scales]
      .sort((left, right) => left.value - right.value)
      .map(({ value, word, maxCount }) => ({ value, word, maxCount })),
    joinerUnitsBeforeTen: data.joiners.unitsBeforeTen.word,
    joinerBelowThousand: data.joiners.belowThousand.word,
    joinerBetweenGroups: data.joiners.betweenGroups.word,
    minValue: data.limits.min,
    maxValue: data.limits.max,
  };
}

export function phoneTables(data: PhoneData): PhoneTables {
  const operatorByPrefix = data.operators
    .flatMap((operator) => operator.prefixes.map((prefix) => [prefix, operator.id] as const))
    .sort(([left], [right]) => left.localeCompare(right));
  return {
    countryCode: data.plan.countryCode,
    internationalPrefix: data.plan.internationalPrefix,
    trunkPrefix: data.plan.trunkPrefix,
    nationalNumberLength: data.plan.nationalNumberLength,
    grouping: [...data.plan.grouping],
    operators: data.operators.map(({ id, name }) => ({ id, name })),
    operatorByPrefix,
    operatorPrefixLength: operatorByPrefix[0]?.[0].length ?? 0,
  };
}

interface Sourced<T> {
  readonly values: readonly T[];
}

export interface AriaryData {
  readonly currency: { readonly code: string; readonly symbol: string; readonly word: string };
  readonly symbolSeparator: { readonly value: string };
  readonly grouping: { readonly size: number };
  readonly groupSeparators: readonly { readonly id: string; readonly value: string }[];
  readonly readableSeparators: Sourced<string>;
  readonly currencyTokens: Sourced<string>;
  readonly legacyFranc: { readonly symbol: string; readonly francsPerAriary: number };
  readonly limits: { readonly min: number; readonly max: number };
}

export interface AriaryTables {
  readonly currencySymbol: string;
  readonly currencyWord: string;
  readonly symbolSeparator: string;
  readonly groupSize: number;
  /** Separator id → character; the first entry is the default. */
  readonly groupSeparators: readonly (readonly [string, string])[];
  readonly readableSeparators: readonly string[];
  /** Lower-cased, longest first, so `Ariary` is tried before `Ar`. */
  readonly currencyTokens: readonly string[];
  readonly francSymbol: string;
  readonly francsPerAriary: number;
  readonly minAmount: number;
  readonly maxAmount: number;
}

export function ariaryTables(data: AriaryData): AriaryTables {
  return {
    currencySymbol: data.currency.symbol,
    currencyWord: data.currency.word,
    symbolSeparator: data.symbolSeparator.value,
    groupSize: data.grouping.size,
    groupSeparators: data.groupSeparators.map(({ id, value }) => [id, value] as const),
    readableSeparators: [...data.readableSeparators.values],
    currencyTokens: data.currencyTokens.values
      .map((token) => token.toLowerCase())
      .sort((left, right) => right.length - left.length || left.localeCompare(right)),
    francSymbol: data.legacyFranc.symbol,
    francsPerAriary: data.legacyFranc.francsPerAriary,
    minAmount: data.limits.min,
    maxAmount: data.limits.max,
  };
}

export interface DateData {
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
  readonly limits: { readonly minYear: number; readonly maxYear: number };
}

export interface DateTables {
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
}

function byNumber<T>(
  entries: readonly T[],
  numberOf: (entry: T) => number,
  pick: (entry: T) => string,
): string[] {
  const sorted = [...entries].sort((left, right) => numberOf(left) - numberOf(right));
  sorted.forEach((entry, index) => {
    if (numberOf(entry) !== index + 1) throw new Error(`spec gap at number ${String(index + 1)}`);
  });
  return ['', ...sorted.map(pick)];
}

export function dateTables(data: DateData): DateTables {
  const monthNumber = (month: DateData['months'][number]): number => month.number;
  const weekdayNumber = (weekday: DateData['weekdays'][number]): number => weekday.isoNumber;
  return {
    monthNames: byNumber(data.months, monthNumber, (month) => month.name),
    monthAbbreviations: byNumber(data.months, monthNumber, (month) => month.abbreviation),
    weekdayNames: byNumber(data.weekdays, weekdayNumber, (weekday) => weekday.name),
    weekdayAbbreviations: byNumber(data.weekdays, weekdayNumber, (weekday) => weekday.abbreviation),
    styles: data.styles.map(({ id, pattern }) => [id, pattern] as const),
    minYear: data.limits.minYear,
    maxYear: data.limits.maxYear,
  };
}
