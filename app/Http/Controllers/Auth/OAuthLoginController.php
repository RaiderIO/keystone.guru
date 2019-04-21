<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Socialite\Facades\Socialite;

abstract class OAuthLoginController extends LoginController
{
    /**
     * @return string The driver for this OAuth login request
     */
    protected abstract function getDriver();

    /**
     * @param $id string The ID that the auth provider supplied
     * @return mixed A globally uniquely identifyable ID to couple to the user account.
     */
    protected function getOAuthId($id)
    {
        return sprintf('%s@%s', $id, $this->getDriver());
    }

    /**
     * Redirect the user to the OAuth authentication page.
     *
     * @param Request $request
     * @return Response
     */
    public function redirectToProvider(Request $request)
    {
        $this->redirectTo = $request->get('redirect', '/');
        return Socialite::driver($this->getDriver())->redirect();
    }

    /**
     * Obtain the user information from the OAuth provider.
     *
     * @return \Illuminate\Http\Response
     */
    protected function fetchUser()
    {
        return Socialite::driver($this->getDriver())->user();
    }
}
