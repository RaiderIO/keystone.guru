<?php

namespace App\Service\Cookies;

class CookieService implements CookieServiceInterface
{
    public function setCookie(
        string $key,
        mixed  $value,
        int    $expires = 0,
        string $path = '/',
        string $domain = null,
        bool   $secure = true,
        bool   $httponly = false,
    ): void {
        $_COOKIE[$key] = $value;
        setcookie(
            $key,
            (string)$value,
            [
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => config('app.env') === 'local' ? false : $secure,
                'httponly' => $httponly,
            ],
        );
    }
}
