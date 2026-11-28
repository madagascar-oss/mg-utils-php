// ariary: spec data → lookup tables → PHP files.
import type { PhpOutput, Sourced } from "./shared.ts";
import {
  dataClass,
  pascalCase,
  phpFile,
  phpLiteral,
  ROOT_NAMESPACE,
} from "../emit.ts";

interface AriaryData {
  readonly currency: {
    readonly code: string;
    readonly symbol: string;
    readonly word: string;
  };
  readonly symbolSeparator: { readonly value: string };
  readonly decimalSeparators: readonly {
    readonly id: string;
    readonly value: string;
  }[];
  readonly grouping: { readonly size: number };
  readonly groupSeparators: readonly {
    readonly id: string;
    readonly value: string;
  }[];
  readonly readableSeparators: Sourced<string>;
  readonly currencyTokens: Sourced<string>;
  readonly legacyFranc: {
    readonly code: string;
    readonly symbol: string;
    readonly francsPerAriary: number;
  };
  readonly limits: {
    readonly min: number;
    readonly maxTextLength: number;
    readonly maxReadLength: number;
  };
}

interface AriaryTables {
  readonly currencySymbol: string;
  readonly currencyCode: string;
  readonly francCode: string;
  readonly currencyWord: string;
  readonly symbolSeparator: string;
  readonly groupSize: number;
  /** Separator id → character; the first entry is the default. */
  readonly groupSeparators: readonly (readonly [string, string])[];
  /** Decimal option id → character; the first entry is the default. */
  readonly decimalSeparators: readonly (readonly [string, string])[];
  readonly readableSeparators: readonly string[];
  /** Lower-cased, longest first, so `Ariary` is tried before `Ar`. */
  readonly currencyTokens: readonly string[];
  readonly francSymbol: string;
  readonly francsPerAriary: number;
  /** 10 ÷ francsPerAriary: dividing by it is multiplying by this, then shifting one place. */
  readonly francDivisionFactor: number;
  readonly minAmount: number;
  readonly maxTextLength: number;
  readonly maxReadLength: number;
}

const DECIMAL_BASE = 10;

/** Exact decimal division needs a rate that divides 10, such as 5 (1 Ar = 5 FMG). */
function divisionFactor(francsPerAriary: number): number {
  if (DECIMAL_BASE % francsPerAriary !== 0) {
    throw new Error(
      `francsPerAriary ${String(francsPerAriary)} does not divide 10`,
    );
  }
  return DECIMAL_BASE / francsPerAriary;
}

function ariaryTables(data: AriaryData): AriaryTables {
  return {
    currencySymbol: data.currency.symbol,
    currencyCode: data.currency.code,
    francCode: data.legacyFranc.code,
    currencyWord: data.currency.word,
    symbolSeparator: data.symbolSeparator.value,
    groupSize: data.grouping.size,
    groupSeparators: data.groupSeparators.map(
      ({ id, value }) => [id, value] as const,
    ),
    decimalSeparators: data.decimalSeparators.map(
      ({ id, value }) => [id, value] as const,
    ),
    readableSeparators: [...data.readableSeparators.values],
    currencyTokens: data.currencyTokens.values
      .map((token) => token.toLowerCase())
      .sort(
        (left, right) =>
          right.length - left.length || left.localeCompare(right),
      ),
    francSymbol: data.legacyFranc.symbol,
    francsPerAriary: data.legacyFranc.francsPerAriary,
    francDivisionFactor: divisionFactor(data.legacyFranc.francsPerAriary),
    minAmount: data.limits.min,
    maxTextLength: data.limits.maxTextLength,
    maxReadLength: data.limits.maxReadLength,
  };
}

interface SeparatorEnum {
  readonly name: string;
  readonly doc: string;
  readonly separators: readonly (readonly [string, string])[];
}

/** A backed enum of separator options, with the default and the character of each case. */
function separatorEnum({ name, doc, separators }: SeparatorEnum): string {
  const cases = separators
    .map(([id]) => `    case ${pascalCase(id)} = ${phpLiteral(id)};`)
    .join("\n");
  const characters = separators
    .map(
      ([id, value]) =>
        `            self::${pascalCase(id)} => ${phpLiteral(value)},`,
    )
    .join("\n");
  return phpFile(
    `${ROOT_NAMESPACE}\\Ariary`,
    `/**
${doc}
 */
enum ${name}: string
{
${cases}

    /** The option used when none is given: the first one in the spec, the Malagasy way. */
    public const self DEFAULT = self::${pascalCase(separators[0]?.[0] ?? "")};

    /**
     * The separator character.
     *
     * @return non-empty-string
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

const GROUPING_DOC = ` * Thousand separator for formatting and reading amounts. Madagascar uses a space or a dot;
 * \`Comma\` is only for text in another convention (with \`DecimalSeparator::Dot\`).
 *
 * Example: \`Ariary::fromInt(1500)->format(Grouping::Dot)\` returns \`'Ar 1.500'\`.`;

const DECIMAL_DOC = ` * Decimal separator for formatting and reading amounts. Madagascar uses a comma; \`Dot\` is
 * only for text in another convention.
 *
 * Example: \`Ariary::fromFloat(1500.5)->format()\` returns \`'Ar 1 500,5'\`.`;

export function emit(data: AriaryData, source: string): PhpOutput[] {
  const tables = ariaryTables(data);
  return [
    {
      path: "Ariary/Generated/Data.php",
      source: dataClass(
        `${ROOT_NAMESPACE}\\Ariary\\Generated`,
        [
          {
            name: "CURRENCY_SYMBOL",
            type: "string",
            value: tables.currencySymbol,
          },
          { name: "CURRENCY_WORD", type: "string", value: tables.currencyWord },
          {
            name: "SYMBOL_SEPARATOR",
            type: "string",
            value: tables.symbolSeparator,
          },
          { name: "GROUP_SIZE", type: "int", value: tables.groupSize },
          {
            name: "READABLE_SEPARATORS",
            type: "array",
            value: tables.readableSeparators,
            varDoc: "list<string>",
          },
          {
            name: "CURRENCY_TOKENS",
            type: "array",
            value: tables.currencyTokens,
            varDoc: "list<string>",
          },
          { name: "FRANC_SYMBOL", type: "string", value: tables.francSymbol },
          { name: "FRANC_CODE", type: "string", value: tables.francCode },
          { name: "CURRENCY_CODE", type: "string", value: tables.currencyCode },
          {
            name: "FRANCS_PER_ARIARY",
            type: "int",
            value: tables.francsPerAriary,
          },
          {
            name: "FRANC_DIVISION_FACTOR",
            type: "int",
            value: tables.francDivisionFactor,
          },
          { name: "MIN_AMOUNT", type: "int", value: tables.minAmount },
          { name: "MAX_TEXT_LENGTH", type: "int", value: tables.maxTextLength },
          { name: "MAX_READ_LENGTH", type: "int", value: tables.maxReadLength },
        ],
        source,
      ),
    },
    {
      path: "Ariary/Grouping.php",
      source: separatorEnum({
        name: "Grouping",
        doc: GROUPING_DOC,
        separators: tables.groupSeparators,
      }),
    },
    {
      path: "Ariary/DecimalSeparator.php",
      source: separatorEnum({
        name: "DecimalSeparator",
        doc: DECIMAL_DOC,
        separators: tables.decimalSeparators,
      }),
    },
  ];
}
