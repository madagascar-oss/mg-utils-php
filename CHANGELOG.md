# Changelog

`mdgascar/mdg-utils` uses the same version number as the `@mdgascar/*` JavaScript
packages: the same version means the same behaviour. Merging `dev` into `main` tags the
version in the first heading below and creates the GitHub release; Packagist picks up the tag.
What changed in the behaviour itself is in the
[spec changelog](https://mdgascar.github.io/mdg-utils-docs/docs/changelog/).

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
