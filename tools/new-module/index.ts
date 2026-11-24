// Scaffolds the PHP side of a module that already exists in spec/.
// Usage: just new-module <name>   (lower-case, e.g. ariary)
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { MODULES_DIR, ROOT_DIR } from '../lib/spec.ts';

const MODULE_NAME = /^[a-z][a-z0-9]*$/;
const name = process.argv[2] ?? '';
if (!MODULE_NAME.test(name)) {
  console.error('Usage: just new-module <name>  (lower-case letters and digits, e.g. ariary)');
  process.exit(1);
}
if (!existsSync(join(MODULES_DIR, name, 'data.yaml'))) {
  console.error(`Module "${name}" is not in spec/. Add it to mg-utils-spec and bump the submodule first.`);
  process.exit(1);
}
const pascal = name.charAt(0).toUpperCase() + name.slice(1);
if (existsSync(join(ROOT_DIR, 'src', pascal))) {
  console.error(`Module "${name}" already exists.`);
  process.exit(1);
}
const fnName = `check${pascal}Input`;

function write(path: string, content: string): void {
  mkdirSync(join(path, '..'), { recursive: true });
  writeFileSync(path, content);
  console.log(`created ${path.slice(ROOT_DIR.length)}`);
}

const phpDir = ROOT_DIR;
write(
  join(phpDir, 'src', pascal, `${pascal}.php`),
  `<?php

declare(strict_types=1);

namespace Madagascar\\MgUtils\\${pascal};

/**
 * TODO: describe ${name}.
 */
final class ${pascal}
{
    /**
     * Placeholder: replace with the module's real function, spec first.
     *
     * Example: \`${pascal}::checkInput('example')\` returns \`'example'\`.
     */
    public static function checkInput(string $input): string
    {
        return $input;
    }
}
`,
);
write(
  join(phpDir, 'tests', 'Conformance', 'functions', `${name}.php`),
  `<?php

declare(strict_types=1);

use Madagascar\\MgUtils\\${pascal}\\${pascal};

return [
    // @phpstan-ignore argument.type
    '${fnName}' => static fn(mixed $input): string => ${pascal}::checkInput($input),
];
`,
);

console.log('Next: run `just codegen` and `just test`.');
