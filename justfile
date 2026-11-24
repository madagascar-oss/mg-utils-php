# mg-utils-php — every command in one place. Run `just` to list them.
set shell := ["bash", "-euo", "pipefail", "-c"]

# List commands
default:
    @just --list

# Fetch the spec submodule and install dependencies
setup:
    git submodule update --init
    command -v pnpm >/dev/null || npm install --global pnpm@10
    pnpm install --frozen-lockfile
    composer install

# Regenerate src/*/Generated and src/{ErrorCode,Spec}.php from spec/ (never edit them by hand)
codegen:
    pnpm codegen

# Fail if generated code drifted from the spec
check:
    pnpm typecheck
    pnpm codegen
    git diff --exit-code -- src || (echo "Generated code is out of date: run 'just codegen' and commit." && exit 1)

# Coding standard, static analysis, mess detector, magic numbers
lint:
    composer lint

# Fix coding standard
format:
    composer cs-fix

# Run spec vectors and unit tests with 100% coverage
test:
    composer test

# Run benchmarks
bench:
    composer bench

# Everything CI runs
ci: check lint test

# Scaffold the PHP side of a module that exists in spec/
new-module name:
    pnpm new-module {{name}}
    pnpm codegen
