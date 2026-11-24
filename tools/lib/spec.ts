import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parse } from 'yaml';

export const ROOT_DIR = fileURLToPath(new URL('../..', import.meta.url));
export const SPEC_DIR = join(ROOT_DIR, 'spec');
export const MODULES_DIR = join(SPEC_DIR, 'modules');

export const MODULE_FILES = ['README.md', 'data.yaml', 'schema.json', 'tests.yaml'] as const;

export function listModules(): string[] {
  return readdirSync(MODULES_DIR, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => entry.name)
    .sort();
}

export function readYaml(path: string): unknown {
  return parse(readFileSync(path, 'utf8'));
}

export function moduleFile(module: string, file: (typeof MODULE_FILES)[number]): string {
  return join(MODULES_DIR, module, file);
}

export function readSpecVersion(): string {
  return readFileSync(join(SPEC_DIR, 'VERSION'), 'utf8').trim();
}

export interface ErrorDefinition {
  readonly code: string;
  readonly description: string;
}

export function readErrors(): readonly ErrorDefinition[] {
  return readYaml(join(SPEC_DIR, 'errors.yaml')) as ErrorDefinition[];
}

const PROVENANCE_KEYS = new Set(['source', 'checked', 'note']);

/** Deep copy of spec data without provenance fields, which native code does not need. */
export function stripProvenance(value: unknown): unknown {
  if (Array.isArray(value)) return value.map(stripProvenance);
  if (value === null || typeof value !== 'object') return value;
  return Object.fromEntries(
    Object.entries(value)
      .filter(([key]) => !PROVENANCE_KEYS.has(key))
      .map(([key, inner]) => [key, stripProvenance(inner)]),
  );
}
