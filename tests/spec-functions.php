<?php

declare(strict_types=1);

// Maps spec function names (spec/*/tests.yaml `fn`) to the PHP API, one file per module in
// spec-functions/, playing the role of js/test-runner/default-functions.json.
// The first function of a module is its default.

$files = glob(__DIR__ . '/spec-functions/*.php');
$functions = [];
foreach ($files === false ? [] : $files as $file) {
    $functions[basename($file, '.php')] = require $file;
}
ksort($functions);

return $functions;
