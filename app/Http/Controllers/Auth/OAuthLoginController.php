<?php

namespace App\Http\Controllers\Auth;

use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Session;

abstract class OAuthLoginController extends LoginController
{
    /**
     * @return string The driver for this OAuth login request
     */
    protected abstract function getDriver();

    protected abstract function getUser($oauthUser, $oAuthId);

    /**
     * @param $id string The ID that the auth provider supplied
     * @return mixed A globally uniquely identifyable ID to couple to the user account.
     */
    protected function getOAuthId($id)
    {
        return sprintf('%s@%s', $id, $this->getDriver());
    }

    /**
     * Checks if a user exists by its username.
     * @param $username string The username to check.
     * @return bool True if the user exists, false if it does not.
     */
    protected function userExistsByUsername($username): bool
    {
        return User::where('name', $username)->first() !== null;
    }

    /**
     * Checks if a user exists by its e-mail address.
     * @param $email string The e-mail address to check.
     * @return bool True if the user exists, false if it does not.
     */
    protected function userExistsByEmail($email): bool
    {
        return User::where('email', $email)->first() !== null;
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
     * @return Response
     */
    protected function fetchUser()
    {
        return Socialite::driver($this->getDriver())->user();
    }

    /**
     * Obtain the user information from Google.
     *
     * @param Request $request
     * @return Response
     */
    public function handleProviderCallback(Request $request)
    {
        /** @var \SocialiteProviders\Manager\OAuth2\User $oauthUser */
        $oauthUser = $this->fetchUser();
        $success = false;

        $oAuthId = $this->getOAuthId($oauthUser->id);
        /** @var User $existingUser */
        $existingUser = User::where('oauth_id', $oAuthId)->first();
        // Does this user exist..
        if ($existingUser === null) {
            // Get a new template user
            $existingUser = $this->getUser($oauthUser, $oAuthId);
            // Only if he/she does not already exists, we cannot just log in that existing user to prevent account takeovers.
            if (!$this->userExistsByEmail($existingUser->email)) {
                // Check if the username doesn't exist yet
                if (!$this->userExistsByUsername($existingUser->name)) {
                    $success = true;
                    // Save it
                    $existingUser->save();

                    // Add it as a user
                    $existingUser->attachRole(Role::where('name', 'user')->first());

                    Session::flash('status', __('controller.oauthlogin.flash.registered_successfully'));
                } else {
                    Session::flash('warning', sprintf(__('controller.oauthlogin.flash.user_exists'), $existingUser->name));
                    $this->redirectTo = '/';
                }
            } else {
                Session::flash('warning', sprintf(__('controller.oauthlogin.flash.email_exists'), $existingUser->email));
                $this->redirectTo = '/';
            }
        } else {
            $success = true;
        }

        // Login either the new or the existing user
        if ($success) {
            Auth::login($existingUser, true);
        }

        return redirect($this->redirectTo);
    }
}
