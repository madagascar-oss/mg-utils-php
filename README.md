# madagascar-oss/mg-utils

Malagasy number words, Madagascar mobile phone numbers and Ariary amounts for PHP. Zero dependencies, PHP 8.3+.

Implements spec 0.1.0.

> This package is a read-only mirror of the `php/` folder of
> [madagascar-oss/mg-utils](https://github.com/madagascar-oss/mg-utils). Open issues and pull
> requests there.

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
PhoneNumber::isValid($userInput);       // bool, never throws
```

```php
use Madagascar\MgUtils\Ariary\Ariary;
use Madagascar\MgUtils\Ariary\Grouping;

Ariary::fromInt(1500)->format();              // 'Ar 1 500' (no-break spaces, never a comma)
Ariary::fromInt(1500)->format(Grouping::Dot); // 'Ar 1.500'
Ariary::parse('1.500 Ar')->amount;            // 1500
Ariary::fromInt(1500)->toWords();             // 'dimanjato sy arivo ariary'
Ariary::fromFmg(7500)->amount;                // 1500
```

Errors throw `Madagascar\MgUtils\MgUtilsException`; check `$exception->errorCode`
(`ErrorCode::OutOfRange`, …), which is the same code in every mg-utils language.

Rules, sources and recorded disagreements:
[spec/words](https://github.com/madagascar-oss/mg-utils/blob/dev/spec/words/README.md),
[spec/phone](https://github.com/madagascar-oss/mg-utils/blob/dev/spec/phone/README.md),
[spec/ariary](https://github.com/madagascar-oss/mg-utils/blob/dev/spec/ariary/README.md).
