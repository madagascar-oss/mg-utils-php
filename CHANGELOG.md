# Changelog

`mdgascar/mdg-utils` uses the same version number as the `@mdgascar/*` JavaScript
packages: the same version means the same behaviour. Merging `dev` into `main` tags the
version in the first heading below and creates the GitHub release; Packagist picks up the tag.
What changed in the behaviour itself is in the
[spec changelog](https://mdgascar.github.io/mdg-utils-docs/docs/changelog/).

## 0.9.0

Implements spec 0.8.0.

- **Breaking:** the API uses the domain's words. `NumberWords::spell(int|float|string)` replaces
  `fromInt()`, `fromFloat()` and the unreleased `fromString()`; `Ariary->spell()` replaces
  `->toWords()`, and `Ariary::spellText()` reads exact decimal text. A float passed to `spell()`
  stays a float (`spell(2.5)` is `roa faingo dimy`), so the `fromInt(2.5)` truncation trap is gone.

- `NumberWords::spell('2000000000000000.9')` and `Ariary::spellText()` read plain
  decimal text exactly, where a float has already lost digits. Text that is not digits with an
  optional `.decimals`, or longer than 100 characters, is `ErrorCode::InvalidFormat`.
- In `NumberSystem::Traditional`, a number of 10¹⁹ or more (text only) is written in the
  standard system.

## 0.8.0

Implements spec 0.7.0.

- `Words\NumberSystem` (`Standard`, `Traditional`): `NumberWords::fromInt($n, NumberSystem::Traditional)`,
  `NumberWords::fromFloat()` and `Ariary::toWords()` take it and name every power of ten
  (`30 000 000` → `telo safatsiroa`). `Standard` stays the default; its output is unchanged.

## 0.7.0

First release on Packagist. Implements spec 0.6.0. PHP 8.3+, zero dependencies.

- `Words\NumberWords`: `fromInt()` and `fromFloat()` spell numbers of any size in Malagasy,
  decimals read with `faingo`.
- `Phone\PhoneNumber`: `parse()`, `tryParse()`, `safeParse()` and `isValid()` for
  Madagascar mobile numbers, with the operator (Orange, Airtel, Yas, Blueline) and E.164,
  national and international formats.
- `Ariary\Ariary`: `fromInt()`, `fromFloat()`, `parse()`, `tryParse()`, `safeParse()`,
  Ariary formatting with space or dot grouping, and exact FMG conversion with `fromFmg()`
  and `formatFmg()`.
- `Date\MalagasyDate`: Malagasy month and weekday names and the short, medium, long, full
  and numeric date styles.
- Errors throw `MdgUtilsException` with an `ErrorCode`; `safeParse()` returns a
  `SafeParseResult` instead of throwing.
