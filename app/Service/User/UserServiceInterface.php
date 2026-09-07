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

    /**
     * Resolves the request's credentials against the database, skipping the cache of already verified credentials.
     */
    public function verifyUserFromAuthenticationHeader(Request $request): BasicAuthenticationResult;

    public function loginAsUser(string $email, string $password): bool;
}
