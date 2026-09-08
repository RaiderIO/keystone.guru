<?php

namespace App\Service\User;

use App\Service\User\Dtos\BasicAuthenticationResult;
use Illuminate\Http\Request;

interface UserServiceInterface
{
    public function loginAsUserFromAuthenticationHeader(Request $request): BasicAuthenticationResult;

    /**
     * Whether a previous request already verified the credentials this request carries - a cache read that never
     * costs a password hash comparison, and that authenticates nobody.
     */
    public function hasVerifiedCredentialsCached(Request $request): bool;

    public function loginAsUser(string $email, string $password): bool;
}
