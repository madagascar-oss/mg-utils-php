# Contributing to mg-utils-php

The [organisation guide](https://github.com/madagascar-oss/.github/blob/main/CONTRIBUTING.md)
and [Code of Conduct](https://github.com/madagascar-oss/.github/blob/main/CODE_OF_CONDUCT.md)
apply too.

## Spec first

Data and test vectors live in [mg-utils-spec](https://github.com/madagascar-oss/mg-utils-spec).
Change them there first. Once the spec is released, Renovate opens a PR here that bumps the
`spec/` submodule; push the code change to that PR until `just ci` passes.

## Set up

```sh
git clone --recurse-submodules https://github.com/madagascar-oss/mg-utils-php.git
cd mg-utils-php
just setup
```

Needs PHP 8.3+ with Composer, Node.js 22.18+ with pnpm (code generation only, never shipped)
and [just](https://just.systems).

## Repository layout

```text
src/<Module>/              public API; src/<Module>/Generated/ comes from codegen
src/{ErrorCode,Spec}.php   generated; MgUtilsException.php is hand-written
tests/<Module>/            unit tests, mirroring src/
tests/Conformance/         runs every spec vector; functions/ maps spec names to the PHP API
benchmarks/                phpbench
spec/                      mg-utils-spec, git submodule (read-only here)
tools/                     codegen, new-module (Node, dev only), coverage gate
docs/                      coding handbook
```

Only `src/`, `composer.json`, `README.md` and `LICENSE` ship to Packagist.

## Commands

| Command                  | Does                                                 |
| ------------------------ | ---------------------------------------------------- |
| `just setup`             | Fetch `spec/`, install dependencies                  |
| `just codegen`           | Regenerate `src/**/Generated` from `spec/`           |
| `just check`             | Fail if generated code drifted from `spec/`          |
| `just lint`              | php-cs-fixer, PHPStan (max), PHPMD, phpmnd           |
| `just test`              | Spec vectors and unit tests, 100% coverage required  |
| `just new-module <name>` | Scaffold the PHP side of a module that is in `spec/` |
| `just ci`                | Everything CI runs                                   |

## Branches and releases

Branch from `dev` as `<type>/<short-name>`, open the PR against `dev`; PRs are squash-merged
with a Conventional Commit title. To release, merge `dev` into `main`, then tag `main` as
`vX.Y.Z`; Packagist picks up the tag. Update "Implements spec x.y.z" in the README when the
spec submodule moves.

## Code rules

See [docs/CODING.md](docs/CODING.md). Most rules are enforced by tools, so CI tells you before
a reviewer does.
