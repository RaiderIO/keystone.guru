<?php

namespace App\Http\Controllers\Auth;

use App\Models\GameServerRegion;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SocialiteProviders\Battlenet\Provider;

class BattleNetLoginController extends OAuthLoginController
{
    protected function getUser($oauthUser, $oAuthId)
    {
        return new User([
            'public_key'      => User::generateRandomPublicKey(),
            'oauth_id'        => $oAuthId,
            // Prefer nickname over full name
            'name'            => $oauthUser->nickname,
            // Email is likely null in Battle.net's case, so make up one to make the database happy
            'email'           => sprintf('%s@battle.net', $oauthUser->id),
            'echo_color'      => randomHexColor(),
            'password'        => '',
            'legal_agreed'    => 1,
            'legal_agreed_ms' => -1,
        ]);
    }

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
}
