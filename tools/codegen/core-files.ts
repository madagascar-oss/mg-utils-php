// The generated files shared by every module: src/ErrorCode.php and src/Spec.php.
import type { ErrorDefinition } from '../lib/spec.ts';
import { INDENT, ROOT_NAMESPACE, constantCase, phpFile, phpString } from './emit.ts';

export function errorCodeFile(errors: readonly ErrorDefinition[]): string {
  const cases = errors.map(
    (error) =>
      `${INDENT}/** ${error.description} */\n${INDENT}case ${constantCase(error.code)} = ${phpString(error.code)};`,
  );
  return phpFile(
    ROOT_NAMESPACE,
    `/**\n * Error code shared by every mg-utils language port.\n */\nenum ErrorCode: string\n{\n${cases.join('\n\n')}\n}`,
  );
}

export function specFile(specVersion: string): string {
  return phpFile(
    ROOT_NAMESPACE,
    `/**\n * The shared spec this package implements.\n */\nfinal class Spec\n{\n${INDENT}public const string VERSION = ${phpString(specVersion)};\n}`,
  );
}
