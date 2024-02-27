<?php

namespace App\Service\User;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserService implements UserServiceInterface
{
    use AuthenticatesUsers;

    public function loginAsUserFromAuthenticationHeader(Request $request): bool
    {
        if (! $request->hasHeader('Authorization')) {
            return false;
        }

        $authentication = $request->header('Authorization');
        if (! Str::startsWith($authentication, 'Basic')) {
            return false;
        }

        $base64 = Str::replace('Basic ', '', $authentication);
        $usernamePw = base64_decode($base64);
        if ($usernamePw === false) {
            return false;
        }

        $explode = explode(':', $usernamePw);
        if (count($explode) !== 2) {
            return false;
        }

        [$username, $password] = $explode;

        return $this->loginAsUser($username, $password);
    }

    public function loginAsUser(string $email, string $password): bool
    {
        return $this->guard()->attempt(
            get_defined_vars()
        );
    }
}
