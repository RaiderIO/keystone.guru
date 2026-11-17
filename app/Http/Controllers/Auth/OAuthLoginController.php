<?php

namespace App\Http\Controllers\Auth;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Session;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

abstract class OAuthLoginController extends LoginController
{
    /**
     * @return string The driver for this OAuth login request
     */
    abstract protected function getDriver(): string;

    /**
     * @param  object $oauthUser
     * @param  mixed  $oAuthId
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
     * A provider that hands out e-mail addresses its owner never confirmed overrides this, so that
     * no account is created under an address the signing-in person may not own.
     *
     * @param object $oauthUser
     */
    protected function hasUnverifiedEmail($oauthUser): bool
    {
        return false;
    }

    /**
     * Brings a provider-supplied name within the register form's name rules: every run of disallowed
     * characters becomes a single dash and the result is cut to the maximum length. A name with
     * nothing usable left falls back to one derived from the provider's user id.
     */
    protected function normalizeName(?string $name, string $providerId): string
    {
        $normalized = $this->toNameFormat($name ?? '');

        if (!$this->passesNameFormatRules($normalized)) {
            $normalized = $this->toNameFormat(sprintf('%s-%s', $this->getDriver(), $providerId));
        }

        return $normalized;
    }

    /**
     * Redirect the user to the OAuth authentication page.
     */
    public function redirectToProvider(
        Request                      $request,
        ReadOnlyModeServiceInterface $readOnlyModeService,
    ): RedirectResponse|SymfonyRedirectResponse {
        if ($readOnlyModeService->isReadOnly()) {
            Session::flash('warning', __('controller.oauthlogin.flash.read_only_mode_enabled'));
            $this->redirectTo = '/';

            return redirect($this->redirectTo);
        }

        $this->redirectTo = $this->resolveRedirectTarget($request, '/');

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
            /** @var User|null $existingUser */
            $existingUser = User::firstWhere('oauth_id', $oAuthId);
            // Does this user exist..
            if ($existingUser === null) {
                if ($this->hasUnverifiedEmail($oauthUser)) {
                    Session::flash('warning', __('controller.oauthlogin.flash.email_not_verified'));

                    return redirect('/');
                }

                // Get a new template user
                $existingUser       = $this->getUser($oauthUser, $oAuthId);
                $existingUser->name = $this->normalizeName($existingUser->name, $oauthUser->id);
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

    private function toNameFormat(string $name): string
    {
        $dashed = trim((string)preg_replace('/[^\pL\pM\pN_-]+/u', '-', $name), '-');

        return trim(mb_substr($dashed, 0, RegisterController::NAME_MAX_LENGTH), '-');
    }

    private function passesNameFormatRules(string $name): bool
    {
        return Validator::make(['name' => $name], ['name' => RegisterController::NAME_FORMAT_RULES])->passes();
    }
}
