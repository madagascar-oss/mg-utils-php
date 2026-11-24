# mg-utils-php

[![CI](https://github.com/madagascar-oss/mg-utils-php/actions/workflows/ci.yml/badge.svg)](https://github.com/madagascar-oss/mg-utils-php/actions/workflows/ci.yml)

Malagasy number words, Madagascar mobile phone numbers, Ariary amounts and dates for PHP. Zero dependencies, PHP 8.3+.

Implements spec 0.3.0.

**Docs and live playground (run PHP in your browser):** [madagascar-oss.github.io/mg-utils-docs](https://madagascar-oss.github.io/mg-utils-docs/docs/installation/using-composer/)

Data and test vectors come from [mg-utils-spec](https://github.com/madagascar-oss/mg-utils-spec),
included as a git submodule at `spec/`. The JavaScript port is
[mg-utils-js](https://github.com/madagascar-oss/mg-utils-js).

```sh
composer require madagascar-oss/mg-utils
```

```php
use Madagascar\MgUtils\Words\NumberWords;

NumberWords::fromInt(21);   // 'iraika amby roapolo'
NumberWords::fromInt(2026); // 'enina amby roapolo sy roa arivo'
```

```php
use Madagascar\MgUtils\Phone\PhoneNumber;
use Madagascar\MgUtils\Phone\PhoneNumberFormat;

$phone = PhoneNumber::parse('034 12 345 67');
$phone->e164;                                     // '+261341234567'
$phone->operator->displayName();                  // 'Telma'
$phone->format(PhoneNumberFormat::International); // '+261 34 12 345 67'

PhoneNumber::tryParse('035 12 345 67'); // null
PhoneNumber::safeParse('035 12 345 67')->error?->errorCode; // ErrorCode::UnknownOperator
PhoneNumber::isValid($userInput);       // bool, never throws
```

```php
use Madagascar\MgUtils\Ariary\Ariary;
use Madagascar\MgUtils\Ariary\Grouping;

Ariary::fromInt(1500)->format();              // 'Ar 1 500' (no-break spaces, never a comma)
Ariary::fromInt(1500)->format(Grouping::Dot); // 'Ar 1.500'
Ariary::parse('1.500 Ar')->amount;            // 1500
Ariary::safeParse('1,500')->error?->errorCode; // ErrorCode::InvalidFormat
Ariary::fromInt(1500)->toWords();             // 'dimanjato sy arivo ariary'
Ariary::fromFmg(7500)->amount;                // 1500
```

```php
use Madagascar\MgUtils\Date\DateStyle;
use Madagascar\MgUtils\Date\MalagasyDate;

MalagasyDate::fromString('2026-11-23')->format();                // '23 Novambra 2026'
MalagasyDate::fromString('2026-11-23')->format(DateStyle::Full); // 'Alatsinainy 23 Novambra 2026'
MalagasyDate::fromDateTime(new DateTimeImmutable())->format(DateStyle::Numeric);
MalagasyDate::monthName(6);                                      // 'Jona'
```

Errors throw `Madagascar\MgUtils\MgUtilsException`; check `$exception->errorCode`
(`ErrorCode::OutOfRange`, …), which is the same code in every mg-utils language.

Rules, sources and recorded disagreements:
[words](https://github.com/madagascar-oss/mg-utils-spec/blob/dev/modules/words/README.md),
[phone](https://github.com/madagascar-oss/mg-utils-spec/blob/dev/modules/phone/README.md),
[ariary](https://github.com/madagascar-oss/mg-utils-spec/blob/dev/modules/ariary/README.md),
[date](https://github.com/madagascar-oss/mg-utils-spec/blob/dev/modules/date/README.md).

## Contributing

Data or vector changes go to [mg-utils-spec](https://github.com/madagascar-oss/mg-utils-spec)
first. Code changes: see [CONTRIBUTING.md](CONTRIBUTING.md).
