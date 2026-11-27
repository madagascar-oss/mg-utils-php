// words: spec data → lookup tables → PHP files.
import type { PhpOutput, Valued, Worded } from "./shared.ts";
import { dataClass, ROOT_NAMESPACE } from "../emit.ts";

interface WordsData {
  readonly zero: Worded;
  readonly units: readonly Valued[];
  readonly unitOneInCompound: Worded;
  readonly tens: readonly Valued[];
  readonly hundreds: readonly Valued[];
  readonly scales: readonly (Valued & { readonly maxCount?: number })[];
  readonly traditionalScales: readonly Valued[];
  readonly decimalSeparator: Worded;
  readonly joiners: Readonly<
    Record<"unitsBeforeTen" | "belowThousand" | "betweenGroups", Worded>
  >;
  readonly limits: { readonly min: number };
}

interface WordsTables {
  readonly zeroWord: string;
  readonly unitWords: readonly string[];
  readonly unitOneInCompound: string;
  readonly tenWords: readonly string[];
  readonly hundredWords: readonly string[];
  readonly scales: readonly Scale[];
  readonly traditionalScales: readonly Scale[];
  readonly decimalSeparator: string;
  readonly joinerUnitsBeforeTen: string;
  readonly joinerBelowThousand: string;
  readonly joinerBetweenGroups: string;
  readonly minValue: number;
}

// A group word, the power of ten it stands for, and how many digits its count takes
// (null for the largest group, whose count has no limit).
interface Scale {
  readonly word: string;
  readonly exponent: number;
  readonly countDigits: number | null;
}

// Place values of the digits each word table covers.
const UNIT_STEP = 1;

const TEN_STEP = 10;

const HUNDRED_STEP = 100;

const DECIMAL_BASE = 10;

const POWER_OF_TEN = /^10*$/;

const ALL_NINES = /^9+$/;

/** Turns spec scales into exponents and count widths, checking they tile the digits with no gap. */
function scaleTable(scales: WordsData["scales"]): Scale[] {
  const sorted = [...scales].sort((left, right) => left.value - right.value);
  return sorted.map(({ value, word, maxCount }, index) => {
    const digits = String(value);
    if (!POWER_OF_TEN.test(digits))
      throw new Error(`scale ${word}: not a power of ten`);
    const exponent = digits.length - 1;
    const next = sorted[index + 1];
    if (maxCount === undefined) {
      if (next !== undefined)
        throw new Error(`scale ${word}: only the largest may omit maxCount`);
      return { word, exponent, countDigits: null };
    }
    const countDigits = String(maxCount).length;
    if (!ALL_NINES.test(String(maxCount)))
      throw new Error(`scale ${word}: maxCount not 9, 99, …`);
    if (next === undefined)
      throw new Error(`scale ${word}: the largest scale must omit maxCount`);
    if (value * DECIMAL_BASE ** countDigits !== next.value) {
      throw new Error(`scale ${word}: count does not reach ${next.word}`);
    }
    return { word, exponent, countDigits };
  });
}

/**
 * Turns the traditional scales (rule 12) into the same shape: one digit per power of ten, each
 * the next power up, and the largest taking every digit left (no port reaches past it).
 */
function traditionalScaleTable(
  scales: WordsData["traditionalScales"],
): Scale[] {
  const sorted = [...scales].sort((left, right) => left.value - right.value);
  return sorted.map(({ value, word }, index) => {
    const digits = String(value);
    if (!POWER_OF_TEN.test(digits))
      throw new Error(`traditional scale ${word}: not a power of ten`);
    const next = sorted[index + 1];
    if (next === undefined)
      return { word, exponent: digits.length - 1, countDigits: null };
    if (value * DECIMAL_BASE !== next.value) {
      throw new Error(
        `traditional scale ${word}: the next name is not 10 times larger`,
      );
    }
    return { word, exponent: digits.length - 1, countDigits: 1 };
  });
}

/** Words indexed by digit (index 0 is ''), checked for gaps against the spec values. */
function indexedWords(entries: readonly Valued[], step: number): string[] {
  return ["", ...entries.map((entry) => entry.word)].map((word, digit) => {
    const expected = digit * step;
    const found = entries.find((entry) => entry.value === expected);
    if (digit > 0 && found?.word !== word)
      throw new Error(`spec gap at value ${String(expected)}`);
    return word;
  });
}

function wordsTables(data: WordsData): WordsTables {
  return {
    zeroWord: data.zero.word,
    unitWords: indexedWords(data.units, UNIT_STEP),
    unitOneInCompound: data.unitOneInCompound.word,
    tenWords: indexedWords(data.tens, TEN_STEP),
    hundredWords: indexedWords(data.hundreds, HUNDRED_STEP),
    scales: scaleTable(data.scales),
    traditionalScales: traditionalScaleTable(data.traditionalScales),
    decimalSeparator: data.decimalSeparator.word,
    joinerUnitsBeforeTen: data.joiners.unitsBeforeTen.word,
    joinerBelowThousand: data.joiners.belowThousand.word,
    joinerBetweenGroups: data.joiners.betweenGroups.word,
    minValue: data.limits.min,
  };
}

// countDigits null = no limit (the largest group).
const SCALE_TYPE =
  "non-empty-list<array{word: string, exponent: int, countDigits: int|null}>";

export function emit(data: WordsData, source: string): PhpOutput[] {
  const tables = wordsTables(data);
  const namespace = `${ROOT_NAMESPACE}\\Words\\Generated`;
  return [
    {
      path: "Words/Generated/Data.php",
      source: dataClass(
        namespace,
        [
          { name: "ZERO_WORD", type: "string", value: tables.zeroWord },
          {
            name: "UNIT_WORDS",
            type: "array",
            value: tables.unitWords,
            varDoc: "list<string>",
          },
          {
            name: "UNIT_ONE_IN_COMPOUND",
            type: "string",
            value: tables.unitOneInCompound,
          },
          {
            name: "TEN_WORDS",
            type: "array",
            value: tables.tenWords,
            varDoc: "list<string>",
          },
          {
            name: "HUNDRED_WORDS",
            type: "array",
            value: tables.hundredWords,
            varDoc: "list<string>",
          },
          {
            name: "SCALES",
            type: "array",
            value: tables.scales,
            varDoc: SCALE_TYPE,
          },
          {
            name: "TRADITIONAL_SCALES",
            type: "array",
            value: tables.traditionalScales,
            varDoc: SCALE_TYPE,
          },
          {
            name: "DECIMAL_SEPARATOR",
            type: "string",
            value: tables.decimalSeparator,
          },
          {
            name: "JOINER_UNITS_BEFORE_TEN",
            type: "string",
            value: tables.joinerUnitsBeforeTen,
          },
          {
            name: "JOINER_BELOW_THOUSAND",
            type: "string",
            value: tables.joinerBelowThousand,
          },
          {
            name: "JOINER_BETWEEN_GROUPS",
            type: "string",
            value: tables.joinerBetweenGroups,
          },
          { name: "MIN_VALUE", type: "int", value: tables.minValue },
        ],
        source,
      ),
    },
  ];
}
