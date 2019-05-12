<?php

namespace App\Http\Controllers\Auth;

use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

abstract class OAuthLoginController extends LoginController
{
    /**
     * @return string The driver for this OAuth login request
     */
    protected abstract function getDriver();

    protected abstract function getEmailAddress($oauthUser);

    protected abstract function createUser($oauthUser, $oAuthId, $email);

    /**
     * @param $id string The ID that the auth provider supplied
     * @return mixed A globally uniquely identifyable ID to couple to the user account.
     */
    protected function getOAuthId($id)
    {
        return sprintf('%s@%s', $id, $this->getDriver());
    }

    /**
     * Checks if a user exists by its e-mail address.
     * @param $email string The e-mail address to check.
     * @return bool True if the user exists, false if it does not.
     */
    protected function userExistsByEmail($email)
    {
        return User::where('email', $email)->get()->first() !== null;
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

    /**
     * Obtain the user information from Google.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(Request $request)
    {
        /** @var \SocialiteProviders\Manager\OAuth2\User $oauthUser */
        $oauthUser = $this->fetchUser();
        $success = true;

        $oAuthId = $this->getOAuthId($oauthUser->id);
        /** @var User $existingUser */
        $existingUser = User::where('oauth_id', $oAuthId)->first();
        // Does this user exist..
        if ($existingUser === null) {
            // Attach User role to any new user
            $userRole = Role::where('name', 'user')->first();

            $email = $this->getEmailAddress($oauthUser);

            // Only if he/she does not already exists, we cannot just log in that existing user to prevent account takeovers.
            if (!$this->userExistsByEmail($email)) {
                // Create a new user
                $existingUser = $this->createUser($oauthUser, $oAuthId, $email);

                $existingUser->attachRole($userRole);

                \Session::flash('status', __('Registered successfully. Enjoy the website!'));
            } else {
                \Session::flash('warning', sprintf(__('There is already a user with the e-mail address %s. Did you already register before?'), $email));

                // Default to home page
                $this->redirectTo = '/';
                $success = false;
            }
        }

        // Login either the new or the existing user
        if ($success) {
            Auth::login($existingUser, true);
        }

        return redirect($this->redirectTo);
    }
}
