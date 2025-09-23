<?php

namespace App\Http\Controllers\Auth;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Session;

abstract class OAuthLoginController extends LoginController
{
    /**
     * @return string The driver for this OAuth login request
     */
    abstract protected function getDriver(): string;

    /**
     * @return mixed
     */
    abstract protected function getUser($oauthUser, $oAuthId);

    /**
     * @param        $id string The ID that the auth provider supplied
     * @return mixed A globally uniquely identifyable ID to couple to the user account.
     */
    protected function getOAuthId(string $id)
    {
        return sprintf('%s@%s', $id, $this->getDriver());
    }

    /**
     * Checks if a user exists by its username.
     *
     * @param       $username string The username to check.
     * @return bool True if the user exists, false if it does not.
     */
    protected function userExistsByUsername(string $username): bool
    {
        return User::where('name', $username)->exists();
    }

    /**
     * Checks if a user exists by its e-mail address.
     *
     * @param       $email string The e-mail address to check.
     * @return bool True if the user exists, false if it does not.
     */
    protected function userExistsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Redirect the user to the OAuth authentication page.
     */
    public function redirectToProvider(
        Request                      $request,
        ReadOnlyModeServiceInterface $readOnlyModeService,
    ) {
        if ($readOnlyModeService->isReadOnly()) {
            Session::flash('warning', __('controller.oauthlogin.flash.read_only_mode_enabled'));
            $this->redirectTo = '/';

            return redirect($this->redirectTo);
        }

        $this->redirectTo = $request->get('redirect', '/');

        return Socialite::driver($this->getDriver())->redirect();
    }

    /**
     * Obtain the user information from the OAuth provider.
     *
     * @throws InvalidStateException
     * @throws ClientException
     */
    protected function fetchUser(): \Laravel\Socialite\Contracts\User
    {
        return Socialite::driver($this->getDriver())->user();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleProviderCallback(Request $request): RedirectResponse
    {
        try {
            /** @var \SocialiteProviders\Manager\OAuth2\User $oauthUser */
            $oauthUser = $this->fetchUser();
            $success   = false;

            $oAuthId = $this->getOAuthId($oauthUser->id);
            /** @var User $existingUser */
            $existingUser = User::firstWhere('oauth_id', $oAuthId);
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
                        $existingUser->addRole(Role::firstWhere('name', Role::ROLE_USER));

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
        } catch (InvalidStateException|ClientException) {
            Session::flash('warning', __('controller.oauthlogin.flash.permission_denied'));
            $this->redirectTo = '/';
        }

        return redirect($this->redirectTo);
    }
}
