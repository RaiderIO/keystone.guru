<?php

namespace App\Http\Controllers\Auth;

use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleLoginController extends OAuthLoginController
{
    protected function getDriver()
    {
        return 'google';
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(Request $request)
    {
        /** @var \SocialiteProviders\Manager\OAuth2\User $googleUser */
        $googleUser = $this->fetchUser();

        $oAuthId = $this->getOAuthId($googleUser->id);
        /** @var User $existingUser */
        $existingUser = User::where('oauth_id', $oAuthId)->first();
        // Does this user exist..
        if ($existingUser === null) {
            // Attach User role to any new user
            $userRole = Role::where('name', 'user')->first();

            // Create a new user
            $existingUser = User::create([
                'oauth_id' => $oAuthId,
                // Prefer nickname over full name
                'name' => isset($googleUser->nickname) && $googleUser->nickname !== null ? $googleUser->nickname : $googleUser->name,
                'email' => $googleUser->email,
                'password' => '',
                'legal_agreed' => 1,
                'legal_agreed_ms' => -1
            ]);

            $existingUser->attachRole($userRole);
            \Session::flash('status', __('Registered successfully. Enjoy the website!'));
        }

        // Login either the new or the existing user
        Auth::login($existingUser, true);

        return redirect($this->redirectTo);
    }
}
