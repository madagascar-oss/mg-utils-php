# mdg-utils-php

[![CI](https://github.com/mdgascar/mdg-utils-php/actions/workflows/ci.yml/badge.svg)](https://github.com/mdgascar/mdg-utils-php/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/mdgascar/mdg-utils)](https://packagist.org/packages/mdgascar/mdg-utils)

Malagasy number words, Madagascar mobile phone numbers, Ariary amounts and dates for PHP. Zero dependencies, PHP 8.3+.

Implements spec 0.8.0.

**Docs and live playground (run PHP in your browser):** [mdgascar.github.io/mdg-utils-docs](https://mdgascar.github.io/mdg-utils-docs/docs/installation/using-composer/)

Data and test vectors come from [mdg-utils-spec](https://github.com/mdgascar/mdg-utils-spec),
included as a git submodule at `spec/`. The JavaScript port is
[mdg-utils-js](https://github.com/mdgascar/mdg-utils-js).

```sh
composer require mdgascar/mdg-utils
```

Declare `declare(strict_types=1);` in the files that call the library. Without it, PHP turns a
float passed to an `int` parameter into an int before the library sees it:
`NumberWords::fromInt(2.5)` would silently read `2`. With it, that call is a `TypeError`. Use
`fromFloat()` for decimals and `fromString()` for exact digits.

```php
use Mdgascar\MdgUtils\Words\NumberSystem;
use Mdgascar\MdgUtils\Words\NumberWords;

NumberWords::fromInt(21);   // 'iraika amby roapolo'
NumberWords::fromInt(2026); // 'enina amby roapolo sy roa arivo'
NumberWords::fromFloat(3.14); // "telo faingo efatra ambin'ny folo"

// The traditional system names every power of ten (spec words rule 12).
NumberWords::fromInt(30_000_000, NumberSystem::Traditional); // 'telo safatsiroa'

// Floats keep about 15-17 significant digits; text keeps every digit (spec words rule 13).
NumberWords::fromFloat(2_000_000_000_000_000.9); // 'iray sy roa tapitrisa lavitrisa' (the float)
NumberWords::fromString('2000000000000000.9');   // 'roa tapitrisa lavitrisa faingo sivy'
```

```php
use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Mdgascar\MdgUtils\Phone\PhoneNumberFormat;

$phone = PhoneNumber::parse('034 12 345 67');
$phone->e164;                                     // '+261341234567'
$phone->operator->displayName();                  // 'Yas Madagascar'
$phone->format(PhoneNumberFormat::International); // '+261 34 12 345 67'

PhoneNumber::tryParse('031 12 345 67'); // null
PhoneNumber::safeParse('031 12 345 67')->error?->errorCode; // ErrorCode::UnknownOperator
PhoneNumber::isValid($userInput);       // bool, never throws
```

```php
use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;

Ariary::fromInt(1500)->format();              // 'Ar 1 500' (no-break spaces, never a comma)
Ariary::fromFloat(1500.5)->format();          // 'Ar 1 500,5' (decimal comma)
Ariary::fromInt(1500)->format(Grouping::Dot); // 'Ar 1.500'
Ariary::parse('1.500 Ar')->amount;            // 1500
Ariary::parse('1 500,50 Ar')->amount;         // 1500.5
Ariary::parse('1,500.50', Grouping::Comma, DecimalSeparator::Dot)->amount; // 1500.5 (opt-in)
Ariary::safeParse('1.5')->error?->errorCode;  // ErrorCode::InvalidFormat
Ariary::fromInt(1500)->toWords();             // 'dimanjato sy arivo ariary'
Ariary::fromFmg(7501)->amount;                // 1500.2
Ariary::fromFloat(0.07)->toFmg();             // 0.35 (exact)
```

```php
use Mdgascar\MdgUtils\Date\DateStyle;
use Mdgascar\MdgUtils\Date\MalagasyDate;

MalagasyDate::fromString('2026-11-23')->format();                // '23 Novambra 2026'
MalagasyDate::fromString('2026-11-23')->format(DateStyle::Full); // 'Alatsinainy 23 Novambra 2026'
MalagasyDate::fromDateTime(new DateTimeImmutable())->format(DateStyle::Numeric);
MalagasyDate::monthName(6);                                      // 'Jona'
```

Errors throw `Mdgascar\MdgUtils\MdgUtilsException`; check `$exception->errorCode`
(`ErrorCode::OutOfRange`, …), which is the same code in every mdg-utils language.

Rules, sources and recorded disagreements:
[words](https://github.com/mdgascar/mdg-utils-spec/blob/dev/modules/words/README.md),
[phone](https://github.com/mdgascar/mdg-utils-spec/blob/dev/modules/phone/README.md),
[ariary](https://github.com/mdgascar/mdg-utils-spec/blob/dev/modules/ariary/README.md),
[date](https://github.com/mdgascar/mdg-utils-spec/blob/dev/modules/date/README.md).

## Contributing

Data or vector changes go to [mdg-utils-spec](https://github.com/mdgascar/mdg-utils-spec)
first. Code changes: see [CONTRIBUTING.md](CONTRIBUTING.md).

## License

[MIT](LICENSE)
