<?php

namespace App\Service\User;

use App\Service\User\Dtos\BasicAuthenticationResult;
use Illuminate\Http\Request;

interface UserServiceInterface
{
    public function loginAsUserFromAuthenticationHeader(Request $request): BasicAuthenticationResult;

    /**
     * Authenticates the request's credentials only if a previous request already verified them.
     */
    public function loginAsCachedUserFromAuthenticationHeader(Request $request): bool;

    public function loginAsUser(string $email, string $password): bool;
}
