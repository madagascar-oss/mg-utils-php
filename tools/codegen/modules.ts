// One emitter per module turns the shared tables into PHP files.
// Modules without an emitter get a generic `Data::DATA` constant.
import {
  ariaryTables,
  dateTables,
  phoneTables,
  wordsTables,
  type AriaryData,
  type DateData,
  type PhoneData,
  type WordsData,
} from './tables.ts';
import { ROOT_NAMESPACE, dataClass, pascalCase, phpFile, phpLiteral } from './emit.ts';

export interface PhpOutput {
  readonly path: string;
  readonly source: string;
}

const SCALE_TYPE = 'list<array{value: int, word: string, maxCount: int}>';

function words(data: WordsData, source: string): PhpOutput[] {
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

function operatorEnum(operators: readonly { id: string; name: string }[]): string {
  const cases = operators
    .map((op) => `    case ${pascalCase(op.id)} = ${phpLiteral(op.id)};`)
    .join('\n');
  const names = operators
    .map((op) => `            self::${pascalCase(op.id)} => ${phpLiteral(op.name)},`)
    .join('\n');
  return phpFile(
    `${ROOT_NAMESPACE}\\Phone`,
    `/**
 * A Malagasy mobile operator, identified by the same id in every language.
 *
 * Example: \`PhoneNumber::parse('034 12 345 67')->operator === Operator::Telma\`
 */
enum Operator: string
{
${cases}

    /**
     * Display name of the operator, e.g. \`Telma\`.
     */
    public function displayName(): string
    {
        return match ($this) {
${names}
        };
    }
}`,
  );
}

function phone(data: PhoneData, source: string): PhpOutput[] {
  const tables = phoneTables(data);
  return [
    {
      path: 'Phone/Generated/Data.php',
      source: dataClass(
        `${ROOT_NAMESPACE}\\Phone\\Generated`,
        [
          { name: 'COUNTRY_CODE', type: 'string', value: tables.countryCode },
          { name: 'INTERNATIONAL_PREFIX', type: 'string', value: tables.internationalPrefix },
          { name: 'TRUNK_PREFIX', type: 'string', value: tables.trunkPrefix },
          { name: 'NATIONAL_NUMBER_LENGTH', type: 'int', value: tables.nationalNumberLength },
          { name: 'GROUPING', type: 'array', value: tables.grouping, varDoc: 'list<int>' },
          {
            name: 'OPERATOR_BY_PREFIX',
            type: 'array',
            value: Object.fromEntries(tables.operatorByPrefix),
            // PHP stores numeric-string keys such as '34' as integers.
            varDoc: 'array<int|string, string>',
          },
          { name: 'OPERATOR_PREFIX_LENGTH', type: 'int', value: tables.operatorPrefixLength },
        ],
        source,
      ),
    },
    { path: 'Phone/Operator.php', source: operatorEnum(tables.operators) },
  ];
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

function ariary(data: AriaryData, source: string): PhpOutput[] {
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
          { name: 'MAX_AMOUNT', type: 'int', value: tables.maxAmount },
        ],
        source,
      ),
    },
    { path: 'Ariary/Grouping.php', source: groupingEnum(tables.groupSeparators) },
  ];
}

function dateStyleEnum(styles: readonly (readonly [string, string])[]): string {
  const cases = styles.map(([id]) => `    case ${pascalCase(id)} = ${phpLiteral(id)};`).join('\n');
  const patterns = styles
    .map(([id, pattern]) => `            self::${pascalCase(id)} => ${phpLiteral(pattern)},`)
    .join('\n');
  return phpFile(
    `${ROOT_NAMESPACE}\\Date`,
    `/**
 * How \`MalagasyDate::format()\` writes a date. The first case is the default.
 *
 * Example: \`MalagasyDate::fromString('2026-11-23')->format(DateStyle::Full)\` returns
 * \`'Alatsinainy 23 Novambra 2026'\`.
 */
enum DateStyle: string
{
${cases}

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

function date(data: DateData, source: string): PhpOutput[] {
  const tables = dateTables(data);
  return [
    {
      path: 'Date/Generated/Data.php',
      source: dataClass(
        `${ROOT_NAMESPACE}\\Date\\Generated`,
        [
          { name: 'MONTH_NAMES', type: 'array', value: tables.monthNames, varDoc: 'list<string>' },
          {
            name: 'MONTH_ABBREVIATIONS',
            type: 'array',
            value: tables.monthAbbreviations,
            varDoc: 'list<string>',
          },
          {
            name: 'WEEKDAY_NAMES',
            type: 'array',
            value: tables.weekdayNames,
            varDoc: 'list<string>',
          },
          {
            name: 'WEEKDAY_ABBREVIATIONS',
            type: 'array',
            value: tables.weekdayAbbreviations,
            varDoc: 'list<string>',
          },
          { name: 'MIN_YEAR', type: 'int', value: tables.minYear },
          { name: 'MAX_YEAR', type: 'int', value: tables.maxYear },
        ],
        source,
      ),
    },
    { path: 'Date/DateStyle.php', source: dateStyleEnum(tables.styles) },
  ];
}

const EMITTERS: Readonly<Record<string, (data: never, source: string) => PhpOutput[]>> = {
  words,
  phone,
  ariary,
  date,
};

export function moduleFiles(module: string, data: unknown, specVersion: string): PhpOutput[] {
  const source = `spec/modules/${module}/data.yaml (spec ${specVersion})`;
  const emit = EMITTERS[module];
  if (emit !== undefined) return emit(data as never, source);
  const pascal = pascalCase(module);
  return [
    {
      path: `${pascal}/Generated/Data.php`,
      source: dataClass(
        `${ROOT_NAMESPACE}\\${pascal}\\Generated`,
        [{ name: 'DATA', type: 'array', value: data, varDoc: 'array<mixed>' }],
        source,
      ),
    },
  ];
}
