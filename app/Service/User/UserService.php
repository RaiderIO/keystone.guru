<?php

namespace App\Service\User;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class UserService implements UserServiceInterface
{
    use AuthenticatesUsers;

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function longAsUserFromAuthenticationHeader(Request $request): bool
    {
        if (!$request->hasHeader('Authorization')) {
            return false;
        }

        $authentication = $request->header('Authorization');
        if (!Str::startsWith($authentication, 'Basic')) {
            return false;
        }

        $base64     = Str::replace('Basic ', '', $authentication);
        $usernamePw = base64_decode($base64);
        if ($usernamePw === false) {
            return false;
        }

        [$username, $password] = explode(':', $usernamePw);

        return $this->loginAsUser($username, $password);
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return bool
     */
    public function loginAsUser(string $email, string $password): bool
    {
        return $this->guard()->attempt(
            get_defined_vars()
        );
    }
}