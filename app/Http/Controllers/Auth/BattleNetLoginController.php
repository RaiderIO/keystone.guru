<?php

namespace App\Http\Controllers\Auth;

use App\Models\GameServerRegion;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use SocialiteProviders\Battlenet\Provider;

class BattleNetLoginController extends OAuthLoginController
{
    protected function getDriver()
    {
        return 'battlenet';
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function redirectToProvider(Request $request)
    {
        $this->redirectTo = $request->get('redirect', '/');

        $region = $request->get('region', 'us');
        if (GameServerRegion::where('short', $region)->count() === 0) {
            abort(404);
        }

        Provider::setRegion($region);
        return parent::redirectToProvider($request);
    }

    /**
     * Obtain the user information from Google.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(Request $request)
    {
        /** @var \SocialiteProviders\Manager\OAuth2\User $battlenetUser */
        $battlenetUser = $this->fetchUser();

        $oAuthId = $this->getOAuthId($battlenetUser->id);
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
                'name' => $battlenetUser->nickname,
                // Email is likely null in Battle.net's case, so make up one to make the database happy
                'email' => sprintf('%s@battle.net', $battlenetUser->id),
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
