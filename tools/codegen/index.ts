// spec/ → PHP constants under src/. Generated files are written verbatim, so
// `git diff --exit-code src` after `just codegen` detects drift between spec and code.
import { existsSync, mkdirSync, writeFileSync } from "node:fs";
import { dirname, join } from "node:path";
import {
  ROOT_DIR,
  listModules,
  moduleFile,
  readErrors,
  readSpecVersion,
  readYaml,
  stripProvenance,
} from "../lib/spec.ts";
import { errorCodeFile, specFile } from "./core-files.ts";
import { pascalCase } from "./emit.ts";
import { moduleFiles } from "./modules/index.ts";

const specVersion = readSpecVersion();
const errors = readErrors();

// One folder per module under src/, generated files written verbatim.
const phpSourceDir = join(ROOT_DIR, "src");
function writePhp(relativePath: string, source: string): void {
  const path = join(phpSourceDir, relativePath);
  mkdirSync(dirname(path), { recursive: true });
  writeFileSync(path, source);
}

writePhp("ErrorCode.php", errorCodeFile(errors));
writePhp("Spec.php", specFile(specVersion));
const phpModules = listModules().filter((module) =>
  existsSync(join(phpSourceDir, pascalCase(module))),
);
for (const module of phpModules) {
  const data = stripProvenance(readYaml(moduleFile(module, "data.yaml")));
  for (const output of moduleFiles(module, data, specVersion))
    writePhp(output.path, output.source);
}
console.log(`Generated PHP for: ${phpModules.join(", ") || "(no modules)"}`);
