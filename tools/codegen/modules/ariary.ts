// ariary: spec data → lookup tables → PHP files.
import type { PhpOutput, Sourced } from './shared.ts';
import { dataClass, pascalCase, phpFile, phpLiteral, ROOT_NAMESPACE } from '../emit.ts';

interface AriaryData {
  readonly currency: { readonly code: string; readonly symbol: string; readonly word: string };
  readonly symbolSeparator: { readonly value: string };
  readonly grouping: { readonly size: number };
  readonly groupSeparators: readonly { readonly id: string; readonly value: string }[];
  readonly readableSeparators: Sourced<string>;
  readonly currencyTokens: Sourced<string>;
  readonly legacyFranc: { readonly symbol: string; readonly francsPerAriary: number };
  readonly limits: { readonly min: number };
}

interface AriaryTables {
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
}

function ariaryTables(data: AriaryData): AriaryTables {
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
  };
}

function groupingEnum(separators: readonly (readonly [string, string])[]): string {
  const cases = separators
    .map(([id]) => `    case ${pascalCase(id)} = ${phpLiteral(id)};`)
    .join('\n');
  const characters = separators
    .map(([id, value]) => `            self::${pascalCase(id)} => ${phpLiteral(value)},`)
    .join('\n');
  return phpFile(
    `${ROOT_NAMESPACE}\\Ariary`,
    `/**
 * Thousand separator used when formatting amounts. Madagascar never uses a comma.
 *
 * Example: \`Ariary::fromInt(1500)->format(Grouping::Dot)\` returns \`'Ar 1.500'\`.
 */
enum Grouping: string
{
${cases}

    /** The grouping used when none is given: the first one in the spec. */
    public const self DEFAULT = self::${pascalCase(separators[0]?.[0] ?? '')};

    /**
     * The separator character, e.g. a no-break space for \`Grouping::Space\`.
     */
    public function separator(): string
    {
        return match ($this) {
${characters}
        };
    }
}`,
  );
}

export function emit(data: AriaryData, source: string): PhpOutput[] {
  const tables = ariaryTables(data);
  return [
    {
      path: 'Ariary/Generated/Data.php',
      source: dataClass(
        `${ROOT_NAMESPACE}\\Ariary\\Generated`,
        [
          { name: 'CURRENCY_SYMBOL', type: 'string', value: tables.currencySymbol },
          { name: 'CURRENCY_WORD', type: 'string', value: tables.currencyWord },
          { name: 'SYMBOL_SEPARATOR', type: 'string', value: tables.symbolSeparator },
          { name: 'GROUP_SIZE', type: 'int', value: tables.groupSize },
          {
            name: 'READABLE_SEPARATORS',
            type: 'array',
            value: tables.readableSeparators,
            varDoc: 'list<string>',
          },
          {
            name: 'CURRENCY_TOKENS',
            type: 'array',
            value: tables.currencyTokens,
            varDoc: 'list<string>',
          },
          { name: 'FRANC_SYMBOL', type: 'string', value: tables.francSymbol },
          { name: 'FRANCS_PER_ARIARY', type: 'int', value: tables.francsPerAriary },
          { name: 'MIN_AMOUNT', type: 'int', value: tables.minAmount },
        ],
        source,
      ),
    },
    { path: 'Ariary/Grouping.php', source: groupingEnum(tables.groupSeparators) },
  ];
}
