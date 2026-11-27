// phone: spec data → lookup tables → PHP files.
import type { PhpOutput } from "./shared.ts";
import {
  dataClass,
  pascalCase,
  phpFile,
  phpLiteral,
  ROOT_NAMESPACE,
} from "../emit.ts";

interface PhoneData {
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
    readonly prefixes?: readonly string[];
    readonly fixedPrefixes?: readonly string[];
  }[];
  readonly fixedZones: {
    readonly prefix: string;
    readonly zones: readonly { readonly digit: string; readonly name: string }[];
  };
  readonly areas: {
    readonly entries: readonly {
      readonly prefix: string;
      readonly name: string;
      readonly province: string;
    }[];
  };
}

interface PhoneTables {
  readonly countryCode: string;
  readonly internationalPrefix: string;
  readonly trunkPrefix: string;
  readonly nationalNumberLength: number;
  readonly grouping: readonly number[];
  readonly operators: readonly { readonly id: string; readonly name: string }[];
  readonly operatorByPrefix: readonly (readonly [string, string])[];
  readonly fixedPrefixes: readonly string[];
  readonly operatorPrefixLength: number;
  readonly fixedZonePrefix: string;
  readonly fixedZoneDigits: readonly string[];
  readonly areas: PhoneData["areas"]["entries"];
}

function phoneTables(data: PhoneData): PhoneTables {
  const operatorByPrefix = data.operators
    .flatMap(({ id, prefixes = [], fixedPrefixes = [] }) =>
      [...prefixes, ...fixedPrefixes].map((prefix) => [prefix, id] as const),
    )
    .sort(([left], [right]) => left.localeCompare(right));
  return {
    countryCode: data.plan.countryCode,
    internationalPrefix: data.plan.internationalPrefix,
    trunkPrefix: data.plan.trunkPrefix,
    nationalNumberLength: data.plan.nationalNumberLength,
    grouping: [...data.plan.grouping],
    operators: data.operators.map(({ id, name }) => ({ id, name })),
    operatorByPrefix,
    fixedPrefixes: data.operators.flatMap(({ fixedPrefixes = [] }) => fixedPrefixes),
    operatorPrefixLength: operatorByPrefix[0]?.[0].length ?? 0,
    fixedZonePrefix: data.fixedZones.prefix,
    fixedZoneDigits: data.fixedZones.zones.map((zone) => zone.digit),
    // Longest prefix first, so the most precise area wins.
    areas: [...data.areas.entries].sort(
      (left, right) => right.prefix.length - left.prefix.length,
    ),
  };
}

function operatorEnum(
  operators: readonly { id: string; name: string }[],
): string {
  const cases = operators
    .map((op) => `    case ${pascalCase(op.id)} = ${phpLiteral(op.id)};`)
    .join("\n");
  const names = operators
    .map(
      (op) =>
        `            self::${pascalCase(op.id)} => ${phpLiteral(op.name)},`,
    )
    .join("\n");
  return phpFile(
    `${ROOT_NAMESPACE}\\Phone`,
    `/**
 * A Malagasy telephone operator, identified by the same id in every language.
 *
 * Example: \`PhoneNumber::parse('034 12 345 67')->operator === Operator::Yas\`
 */
enum Operator: string
{
${cases}

    /**
     * Display name of the operator, e.g. \`Yas Madagascar\`.
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

export function emit(data: PhoneData, source: string): PhpOutput[] {
  const tables = phoneTables(data);
  return [
    {
      path: "Phone/Generated/Data.php",
      source: dataClass(
        `${ROOT_NAMESPACE}\\Phone\\Generated`,
        [
          { name: "COUNTRY_CODE", type: "string", value: tables.countryCode },
          {
            name: "INTERNATIONAL_PREFIX",
            type: "string",
            value: tables.internationalPrefix,
          },
          { name: "TRUNK_PREFIX", type: "string", value: tables.trunkPrefix },
          {
            name: "NATIONAL_NUMBER_LENGTH",
            type: "int",
            value: tables.nationalNumberLength,
          },
          {
            name: "GROUPING",
            type: "array",
            value: tables.grouping,
            varDoc: "list<int>",
          },
          {
            name: "OPERATOR_BY_PREFIX",
            type: "array",
            value: Object.fromEntries(tables.operatorByPrefix),
            // PHP stores numeric-string keys such as '34' as integers.
            varDoc: "array<int|string, string>",
          },
          {
            name: "FIXED_PREFIXES",
            type: "array",
            value: tables.fixedPrefixes,
            varDoc: "list<string>",
          },
          {
            name: "OPERATOR_PREFIX_LENGTH",
            type: "int",
            value: tables.operatorPrefixLength,
          },
          {
            name: "FIXED_ZONE_PREFIX",
            type: "string",
            value: tables.fixedZonePrefix,
          },
          {
            name: "FIXED_ZONE_DIGITS",
            type: "array",
            value: tables.fixedZoneDigits,
            varDoc: "list<string>",
          },
          {
            name: "AREAS",
            type: "array",
            value: tables.areas,
            varDoc: "list<array{prefix: string, name: string, province: string}>",
          },
        ],
        source,
      ),
    },
    { path: "Phone/Operator.php", source: operatorEnum(tables.operators) },
  ];
}
