<?php

namespace App\Service\User;

use Illuminate\Http\Request;

interface UserServiceInterface
{
    /**
     * @param Request $request
     *
     * @return bool
     */
    public function loginAsUserFromAuthenticationHeader(Request $request): bool;

    /**
     * @param string $email
     * @param string $password
     *
     * @return bool
     */
    public function loginAsUser(string $email, string $password): bool;
}
