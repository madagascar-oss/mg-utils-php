// Picks the emitter for a spec module; modules without one get a generic data constant.
import type { PhpOutput } from './shared.ts';
import * as words from './words.ts';
import * as phone from './phone.ts';
import * as ariary from './ariary.ts';
import * as date from './date.ts';
import { dataClass, pascalCase, ROOT_NAMESPACE } from '../emit.ts';

const EMITTERS: Readonly<Record<string, (data: never, source: string) => PhpOutput[]>> = {
  words: words.emit,
  phone: phone.emit,
  ariary: ariary.emit,
  date: date.emit,
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
