<?php

namespace App\Http\Middleware\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Laratrust\Middleware\Role;

class ApiRole extends Role
{
    protected function unauthorized(): JsonResponse
    {
        $handler = Config::get('laratrust.middleware.handlers.abort');
        $code    = $handler['code'] ?? 403;
        $message = $handler['message'] ?? 'User does not have any of the necessary access rights.';

        return response()->json(['error' => $message], $code);
    }
}
