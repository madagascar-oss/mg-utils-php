# madagascar-oss/mg-utils

Malagasy number words for PHP. Zero dependencies, PHP 8.3+.

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

Errors throw `Madagascar\MgUtils\MgUtilsException`; check `$exception->errorCode`
(`ErrorCode::OutOfRange`, …), which is the same code in every mg-utils language.

Rules, sources and recorded disagreements:
[spec/words](https://github.com/madagascar-oss/mg-utils/blob/dev/spec/words/README.md).
