<?php

namespace App\Http\Controllers\Auth;

use App\Models\GameServerRegion;
use App\Models\User;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    protected function getDriver(): string
    {
        return 'battlenet';
    }

    public function redirectToProvider(Request $request, ReadOnlyModeServiceInterface $readOnlyModeService): RedirectResponse
    {
        $this->redirectTo = $request->get('redirect', '/');

        $region = $request->get('region', GameServerRegion::DEFAULT_REGION);
        if (GameServerRegion::where('short', $region)->doesntExist()) {
            abort(404);
        }

        Provider::setRegion($region);

        return parent::redirectToProvider($request, $readOnlyModeService);
    }
}
