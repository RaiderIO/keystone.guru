<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$server = new Swoole\Http\Server("0.0.0.0", 9501);

$server->on('request', function ($request, $response) use ($app) {
    $laravelRequest = Illuminate\Http\Request::capture();
    $response->header("Content-Type", "text/html");

    // Handle the request using the Laravel application instance
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle($laravelRequest);

    $response->end("Hello from Laravel Octane with Swoole");
});

$server->start();
