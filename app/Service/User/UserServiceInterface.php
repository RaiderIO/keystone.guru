<?php

namespace App\Service\User;

use Illuminate\Http\Request;

interface UserServiceInterface
{
    public function loginAsUserFromAuthenticationHeader(Request $request): bool;

    public function loginAsUser(string $email, string $password): bool;
}
