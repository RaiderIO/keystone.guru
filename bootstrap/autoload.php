<?php

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/

require __DIR__ . '/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Include The Compiled Class File
|--------------------------------------------------------------------------
|
| To dramatically increase your application's performance, you may use a
| compiled class file which contains all of the classes commonly used
| by a request. The Artisan "optimize" is used to create this file.
|
*/

$compiledPath = __DIR__ . '/cache/compiled.php';

if (file_exists($compiledPath)) {
    require $compiledPath;
}

/*
|--------------------------------------------------------------------------
| Keep The Test Suite Off The Real Error Tracker
|--------------------------------------------------------------------------
|
| This file is loaded only as phpunit.xml's bootstrap, and PHPUnit applies its <php> block before
| getting here. That block empties both DSN variables with force="true", but PHPUnit writes only
| putenv() and $_ENV, while Laravel resolves env() through Dotenv's ServerConstAdapter ($_SERVER)
| ahead of its EnvConstAdapter ($_ENV). A DSN in the container's environment therefore beats
| force="true" and the suite transmits its own deliberate report() calls to production Sentry.
| Removing the $_SERVER entries is what lets the emptied $_ENV value win.
|
*/

unset($_SERVER['SENTRY_LARAVEL_DSN'], $_SERVER['SENTRY_DSN']);
