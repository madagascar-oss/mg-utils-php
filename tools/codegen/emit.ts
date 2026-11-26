// PHP source for spec data, written directly in PER-CS style (generated files are not
// reformatted, so the drift check compares exactly what this module prints).

export const GENERATED_HEADER = '// GENERATED — edit spec/ instead. Run `just codegen` to refresh.';
export const ROOT_NAMESPACE = 'Madagascar\\MgUtils';
export const INDENT = '    ';

const NON_ASCII = /[^\x20-\x7e]/g;
const HEX = 16;
const UNICODE_ESCAPE_WIDTH = 4;

/** Single-quoted PHP string, or double-quoted with \\u{…} escapes when it holds non-ASCII. */
export function phpString(value: string): string {
  if (!/[^\x20-\x7e]/.test(value)) {
    return `'${value.replaceAll('\\', '\\\\').replaceAll("'", "\\'")}'`;
  }
  const escaped = value
    .replaceAll('\\', '\\\\')
    .replaceAll('"', '\\"')
    .replaceAll('$', '\\$')
    .replace(
      NON_ASCII,
      (character) =>
        `\\u{${character.charCodeAt(0).toString(HEX).padStart(UNICODE_ESCAPE_WIDTH, '0')}}`,
    );
  return `"${escaped}"`;
}

function isList(value: readonly unknown[] | object): value is readonly unknown[] {
  return Array.isArray(value);
}

/** PHP literal for strings, integers, lists and string-keyed maps. */
export function phpLiteral(value: unknown, depth = 1): string {
  if (typeof value === 'string') return phpString(value);
  if (typeof value === 'number' || typeof value === 'boolean') return String(value);
  if (value === null) return 'null';
  if (typeof value !== 'object')
    throw new Error(`Unsupported value ${String(value)}`);
  const entries = isList(value)
    ? value.map((item) => phpLiteral(item, depth + 1))
    : Object.entries(value).map(
        ([key, item]) => `${phpString(key)} => ${phpLiteral(item, depth + 1)}`,
      );
  if (entries.length === 0) return '[]';
  const inner = INDENT.repeat(depth + 1);
  return `[\n${entries.map((entry) => `${inner}${entry},`).join('\n')}\n${INDENT.repeat(depth)}]`;
}

export function pascalCase(id: string): string {
  return id
    .split(/[-_]/)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('');
}

export function constantCase(code: string): string {
  return code
    .toLowerCase()
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('');
}

export function phpFile(namespace: string, body: string, imports: readonly string[] = []): string {
  const uses =
    imports.length === 0 ? '' : `${imports.map((name) => `use ${name};`).join('\n')}\n\n`;
  return `<?php\n\n${GENERATED_HEADER}\n\ndeclare(strict_types=1);\n\nnamespace ${namespace};\n\n${uses}${body}\n`;
}

export interface PhpConstant {
  readonly name: string;
  readonly type: 'string' | 'int' | 'array';
  readonly value: unknown;
  readonly varDoc?: string;
}

export function dataClass(
  namespace: string,
  constants: readonly PhpConstant[],
  specSource: string,
): string {
  const members = constants.map(({ name, type, value, varDoc }) => {
    const doc = varDoc === undefined ? '' : `${INDENT}/** @var ${varDoc} */\n`;
    return `${doc}${INDENT}public const ${type} ${name} = ${phpLiteral(value)};`;
  });
  return phpFile(
    namespace,
    `/**\n * Lookup tables from ${specSource}.\n *\n * @internal\n */\nfinal class Data\n{\n${members.join('\n\n')}\n}`,
  );
}
