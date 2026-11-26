<?php

declare(strict_types=1);

// Maps spec function names (spec/modules/*/tests.yaml `fn`) to the PHP API, one file per
// module in functions/, playing the role of mdg-utils-js conformance/functions.json.
// The first function of a module is its default.

$files = glob(__DIR__ . '/functions/*.php');
$functions = [];
foreach ($files === false ? [] : $files as $file) {
    $functions[basename($file, '.php')] = require $file;
}
ksort($functions);

return $functions;
