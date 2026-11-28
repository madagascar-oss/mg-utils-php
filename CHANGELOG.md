# Changelog

`mdgascar/mdg-utils` uses the same version number as the `@mdgascar/*` JavaScript
packages: the same version means the same behaviour. Merging `dev` into `main` tags the
version in the first heading below and creates the GitHub release; Packagist picks up the tag.
What changed in the behaviour itself is in the
[spec changelog](https://mdgascar.github.io/mdg-utils-docs/docs/changelog/).

## 0.11.0

Implements spec 0.10.0.

- `Date\MalagasyTime` says the time of day in Malagasy: `MalagasyTime::fromString('14:30')->spell()`
  returns `amin'ny roa sy sasany tolakandro`. `parse()` / `safeParse()` / `tryParse()` read it
  back; `fromParts()` and `fromDateTime()` build one.
- `Ariary\CurrencyDisplay`: `->format(currency: CurrencyDisplay::Code)` writes `MGA 1 500`, and
  `Ariary::formatFmgCode()` writes `MGF 7 500`.

## 0.10.0

Implements spec 0.9.0.

- **Breaking:** `Ariary` keeps the exact decimal `digits` of the amount, so nothing ever rounds.
  The `amount` property is now the `amount()` method, which throws `ErrorCode::PrecisionLoss`
  when an int or float cannot hold every digit (before: `ErrorCode::OutOfRange`, or a silently
  rounded float). `toFmg()` throws the same way; `toFmgDigits()` gives the exact text.
  `Ariary::fromText()`, `fromFmg()` and `formatFmg()` take decimal text. Reading takes up to 329
  characters, so every formatted amount reads back.
- **Breaking (spec):** a count before a group word is one phrase joined by `amby`:
  `NumberWords::spell(201_000_000)` is `iraika amby roanjato tapitrisa` (it shared its words with
  200 000 001).
- `NumberWords::spellOrdinal()` (`fahatelo`), and `NumberWords::parse()` / `safeParse()` /
  `tryParse()`, which read words back into an `ExactNumber` (`value()`, `digits`).
  `ErrorCode::Ambiguous` for the rare words two numbers share.
- `PhoneNumber` reads `036` (Yas) and fixed lines (`020`, `022`), with `type`
  (`PhoneLineType`) and `area` (`PhoneArea`, the town of a geographic number, or `null`).
  `Operator::Gulfsat` is new.
- `MalagasyDate::parse()` / `safeParse()` / `tryParse()` read every style back.

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
