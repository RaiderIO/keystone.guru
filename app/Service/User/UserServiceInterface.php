<?php

namespace App\Service\User;

use Illuminate\Http\Request;

interface UserServiceInterface
{
    /**
     * @return bool
     */
    public function loginAsUserFromAuthenticationHeader(Request $request): bool;

    /**
     *
     * @return bool
     */
    public function loginAsUser(string $email, string $password): bool;
}
