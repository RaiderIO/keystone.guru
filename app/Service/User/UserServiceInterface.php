<?php

namespace App\Service\User;

use App\Service\User\Dtos\BasicAuthenticationResult;
use Illuminate\Http\Request;

interface UserServiceInterface
{
    public function loginAsUserFromAuthenticationHeader(Request $request): BasicAuthenticationResult;

    public function loginAsUser(string $email, string $password): bool;
}
