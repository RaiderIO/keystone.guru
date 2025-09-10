<?php

namespace App\Service\Cookies;

interface CookieServiceInterface
{
    public function setCookie(
        string $key,
        mixed  $value,
        int    $expires = 0,
        string $path = '/',
        string $domain = null,
        bool   $secure = true,
        bool   $httponly = false,
    ): void;
}
