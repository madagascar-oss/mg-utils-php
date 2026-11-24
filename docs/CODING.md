# Coding rules

Code reads like the best-known libraries in each ecosystem (date-fns and zod in TypeScript,
Symfony components in PHP): small pure functions, explicit names, strict types, and every rule
checked by a tool rather than by memory.

## Rules

| #   | Rule                      | In practice                                                                                                                                                                                                                       | Enforced by (JS)                                                                                                    | Enforced by (PHP, Phase 2)                                                 |
| --- | ------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| 1   | Names carry the meaning   | `normalizePhoneNumber(input)`, not `norm(s)`; booleans read as questions (`isValid`, `hasPrefix`)                                                                                                                                 | ESLint `naming-convention`, `id-length`                                                                             | Review                                                                     |
| 2   | One function, one job     | About 20 lines max; complexity ≤ 10; no flag arguments                                                                                                                                                                            | ESLint `max-lines-per-function`, `complexity`, `max-params`, no boolean parameters                                  | PHPMD                                                                      |
| 3   | Pure by default           | Same input → same output; no I/O, globals or hidden state in library code                                                                                                                                                         | ESLint bans `node:*` imports, `process`, `console`, `Date.now`, `Math.random` in `src/`                             | Review, tests                                                              |
| 4   | Immutable data            | `const`, `readonly`, `as const`                                                                                                                                                                                                   | `prefer-const`, `prefer-readonly`, generated `as const`                                                             | `final readonly` classes, PHPStan                                          |
| 5   | Strict types everywhere   | TS `strict`, no `any`                                                                                                                                                                                                             | `tsc` (`strict`, `noUncheckedIndexedAccess`, `exactOptionalPropertyTypes`), typescript-eslint `strict-type-checked` | `declare(strict_types=1)` (php-cs-fixer), PHPStan level max + strict rules |
| 6   | No magic values, anywhere | Every Malagasy value comes from generated spec constants; every other meaningful literal (limits, seeds, run counts, indents) is a named constant, in library code, tools, tests and benchmarks alike. Only 0, 1 and -1 stay bare | Codegen drift check, `@typescript-eslint/no-magic-numbers` on all TS/JS (objects included)                          | Codegen drift check, phpmnd on src, tests, benchmarks and tools            |
| 7   | Fail fast, fail clearly   | Validate at the public boundary; throw `MgError` with a shared spec code                                                                                                                                                          | Spec error vectors                                                                                                  | Spec error vectors                                                         |
| 8   | Small public API          | Export only what users need                                                                                                                                                                                                       | Explicit exports in `index.ts`, `@internal` + `stripInternal`, API Extractor report in `etc/`                       | `@internal`, review                                                        |
| 9   | Idiomatic per language    | camelCase in JS, PER-CS in PHP, PEP 8 in Python                                                                                                                                                                                   | Prettier                                                                                                            | PHP-CS-Fixer                                                               |
| 10  | Delete before adding      | Dead code, unused exports and duplicated logic go in the same PR                                                                                                                                                                  | knip                                                                                                                | PHPStan                                                                    |

## Comments and documentation

- **No comments that restate code.** If a line needs a comment to be understood, rename or
  extract until it doesn't.
- **Comments only for "why":** a non-obvious rule of the Malagasy language, a link to the
  official source, a workaround for a known platform bug.
- **Public API docs are required.** Every exported function gets TSDoc with one `@example`
  (ESLint `jsdoc/require-example`, `tsdoc/syntax`; API Extractor fails on undocumented
  exports). Every public PHP method gets a PHPDoc summary with an example.

## Choosing a pattern

The simplest construct wins; a design pattern needs a concrete reason stated in the PR.

| Situation                                         | Use                                                     | Avoid                                 |
| ------------------------------------------------- | ------------------------------------------------------- | ------------------------------------- |
| Mapping values (prefix → operator, number → word) | Generated lookup tables (`Map`, indexed arrays)         | Long `if` / `switch` chains           |
| A domain value with rules                         | Branded type and a small parse function (`PhoneNumber`) | Raw strings everywhere                |
| Behaviour that truly varies by case               | A table of functions behind one type (`FORMATTERS`)     | Conditionals spread across files      |
| Validation that must not throw                    | `isValid…()` / `safeParse…()` returning a result        | Exceptions for control flow           |
| Sharing logic between modules                     | A small internal function                               | Inheritance hierarchies, base classes |

## Performance

- **Precompute at build time:** codegen turns spec data into lookup structures, so runtime
  work is O(1) lookups.
- **No per-call waste:** regexes and tables are built once at module load.
- **Measure before optimising:** `just bench` (vitest bench and PHPBench); an optimisation PR shows before and after.
- **Readable beats clever** when the measured difference is negligible.

## Tests

- Names describe behaviour: `throws INVALID_TYPE for a bigint`.
- Arrange, act, assert; one behaviour per test.
- Spec vectors cover the contract; language tests cover language-specific edges (types,
  encodings) and fuzzing of parsers.
- 100% branch, line, function and statement coverage on library code; CI fails below it.

## Definition of done for a PR

- [ ] Spec and test vectors updated first, with sources for any data
- [ ] `just test` passes in every touched language
- [ ] No lint, type or coverage errors
- [ ] Public API documented with an example
- [ ] Changeset added if users are affected
