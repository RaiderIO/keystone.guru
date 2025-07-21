<?php

namespace App\Service\Cookies;

class CookieService implements CookieServiceInterface
{
    public function setCookie(string $key, mixed $value, int $expires = 0, string $path = '/', string $domain = null, bool $secure = true, bool $httponly = false): void
    {
        $_COOKIE[$key] = $value;
        setcookie(
            $key,
            $value,
            $expires,
            $path,
            $domain,
            config('app.env') === 'local' ? false : $secure,
            $httponly
        );
    }

}
