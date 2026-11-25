// words: spec data → lookup tables → PHP files.
import type { PhpOutput, Valued, Worded } from './shared.ts';
import { dataClass, ROOT_NAMESPACE } from '../emit.ts';

interface WordsData {
  readonly zero: Worded;
  readonly units: readonly Valued[];
  readonly unitOneInCompound: Worded;
  readonly tens: readonly Valued[];
  readonly hundreds: readonly Valued[];
  readonly scales: readonly (Valued & { readonly maxCount: number })[];
  readonly joiners: Readonly<Record<'unitsBeforeTen' | 'belowThousand' | 'betweenGroups', Worded>>;
  readonly limits: { readonly min: number; readonly max: number };
}

interface WordsTables {
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

function wordsTables(data: WordsData): WordsTables {
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

const SCALE_TYPE = 'list<array{value: int, word: string, maxCount: int}>';

export function emit(data: WordsData, source: string): PhpOutput[] {
  const tables = wordsTables(data);
  const namespace = `${ROOT_NAMESPACE}\\Words\\Generated`;
  return [
    {
      path: 'Words/Generated/Data.php',
      source: dataClass(
        namespace,
        [
          { name: 'ZERO_WORD', type: 'string', value: tables.zeroWord },
          { name: 'UNIT_WORDS', type: 'array', value: tables.unitWords, varDoc: 'list<string>' },
          { name: 'UNIT_ONE_IN_COMPOUND', type: 'string', value: tables.unitOneInCompound },
          { name: 'TEN_WORDS', type: 'array', value: tables.tenWords, varDoc: 'list<string>' },
          {
            name: 'HUNDRED_WORDS',
            type: 'array',
            value: tables.hundredWords,
            varDoc: 'list<string>',
          },
          { name: 'SCALES', type: 'array', value: tables.scales, varDoc: SCALE_TYPE },
          { name: 'JOINER_UNITS_BEFORE_TEN', type: 'string', value: tables.joinerUnitsBeforeTen },
          { name: 'JOINER_BELOW_THOUSAND', type: 'string', value: tables.joinerBelowThousand },
          { name: 'JOINER_BETWEEN_GROUPS', type: 'string', value: tables.joinerBetweenGroups },
          { name: 'MIN_VALUE', type: 'int', value: tables.minValue },
          { name: 'MAX_VALUE', type: 'int', value: tables.maxValue },
        ],
        source,
      ),
    },
  ];
}
