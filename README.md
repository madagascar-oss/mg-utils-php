# mdg-utils-php

[![CI](https://github.com/mdgascar/mdg-utils-php/actions/workflows/ci.yml/badge.svg)](https://github.com/mdgascar/mdg-utils-php/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/mdgascar/mdg-utils)](https://packagist.org/packages/mdgascar/mdg-utils)

Malagasy number words, Madagascar phone numbers, Ariary amounts and dates for PHP. Zero dependencies, PHP 8.3+.

Implements spec 0.10.0.

**Docs and live playground (run PHP in your browser):** [mdgascar.github.io/mdg-utils-docs](https://mdgascar.github.io/mdg-utils-docs/docs/installation/using-composer/)

Data and test vectors come from [mdg-utils-spec](https://github.com/mdgascar/mdg-utils-spec),
included as a git submodule at `spec/`. The JavaScript port is
[mdg-utils-js](https://github.com/mdgascar/mdg-utils-js).

```sh
composer require mdgascar/mdg-utils
```

`NumberWords::spell()` takes an int, a float or decimal text, and keeps each as it is:
`spell(2.5)` reads `roa faingo dimy`. Declare `declare(strict_types=1);` in the files that call
the library anyway: other methods take an `int`, and without it PHP quietly turns a float such
as `2.5` into `2` before the library sees it.

```php
use Mdgascar\MdgUtils\Words\NumberSystem;
use Mdgascar\MdgUtils\Words\NumberWords;

NumberWords::spell(21);   // 'iraika amby roapolo'
NumberWords::spell(2026); // 'enina amby roapolo sy roa arivo'
NumberWords::spell(3.5);  // 'telo faingo dimy'

NumberWords::spellOrdinal(1); // 'voalohany'
NumberWords::spellOrdinal(3); // 'fahatelo'

NumberWords::parse('roa arivo')->value(); // 2000

// The traditional system names every power of ten (spec words rule 12).
NumberWords::spell(30_000_000, NumberSystem::Traditional); // 'telo safatsiroa'
```

```php
use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Mdgascar\MdgUtils\Phone\PhoneNumberFormat;

$phone = PhoneNumber::parse('034 12 345 67');
$phone->e164;                                     // '+261341234567'
$phone->operator->displayName();                  // 'Yas Madagascar'
$phone->type;                                     // PhoneLineType::Mobile
$phone->format(PhoneNumberFormat::International); // '+261 34 12 345 67'

PhoneNumber::tryParse('031 12 345 67'); // null
PhoneNumber::safeParse('031 12 345 67')->error?->errorCode; // ErrorCode::UnknownOperator
PhoneNumber::isValid($userInput);       // bool, never throws
PhoneNumber::parse('020 22 421 19')->area?->name; // 'Antananarivo' (fixed line)
```

```php
use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\CurrencyDisplay;
use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;

Ariary::fromInt(1500)->format();              // 'Ar 1 500' (no-break spaces, never a comma)
Ariary::fromFloat(1500.5)->format();          // 'Ar 1 500,5' (decimal comma)
Ariary::fromInt(1500)->format(Grouping::Dot); // 'Ar 1.500'
Ariary::fromInt(1500)->format(currency: CurrencyDisplay::Code); // 'MGA 1 500'
Ariary::parse('1.500 Ar')->amount();          // 1500
Ariary::parse('1 500,50 Ar')->amount();       // 1500.5
Ariary::parse('1 500,50 Ar')->digits;         // '1500.5' (exact text)
Ariary::parse('1,500.50', Grouping::Comma, DecimalSeparator::Dot)->amount(); // 1500.5 (opt-in)
Ariary::safeParse('1.5')->error?->errorCode;  // ErrorCode::InvalidFormat
Ariary::fromInt(1500)->spell();               // 'dimanjato sy arivo ariary'
Ariary::fromFmg(7501)->amount();              // 1500.2
Ariary::fromFloat(0.07)->toFmg();             // 0.35 (exact)
```

Nothing is ever rounded: `amount()` throws `ErrorCode::PrecisionLoss` when an int or float
cannot hold every digit; `digits` always has them.

```php
```

```php
use Mdgascar\MdgUtils\Date\DateStyle;
use Mdgascar\MdgUtils\Date\MalagasyDate;
use Mdgascar\MdgUtils\Date\MalagasyTime;

MalagasyDate::fromString('2026-11-23')->format();                // '23 Novambra 2026'
MalagasyDate::fromString('2026-11-23')->format(DateStyle::Full); // 'Alatsinainy 23 Novambra 2026'
MalagasyDate::fromDateTime(new DateTimeImmutable())->format(DateStyle::Numeric);
MalagasyDate::parse('23 Novambra 2026')->month;                  // 11
MalagasyTime::fromString('14:30')->spell();                      // "amin'ny roa sy sasany tolakandro"
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
