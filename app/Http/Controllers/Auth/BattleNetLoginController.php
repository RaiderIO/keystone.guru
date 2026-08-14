<?php

namespace App\Http\Controllers\Auth;

use App\Models\GameServerRegion;
use App\Models\User;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use SocialiteProviders\Battlenet\Provider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class BattleNetLoginController extends OAuthLoginController
{
    /**
     * @param object $oauthUser
     * @param mixed  $oAuthId
     */
    protected function getUser($oauthUser, $oAuthId)
    {
        return new User([
            'public_key' => User::generateRandomPublicKey(),
            'oauth_id'   => $oAuthId,
            // Prefer nickname over full name
            'name' => $oauthUser->nickname,
            // Email is likely null in Battle.net's case, so make up one to make the database happy
            'email'        => sprintf('%s@battle.net', $oauthUser->id),
            'echo_color'   => randomHexColor(),
            'password'     => '',
            'legal_agreed' => 1,
        ]);
    }

    protected function getDriver(): string
    {
        return 'battlenet';
    }

    #[\Override]
    public function redirectToProvider(
        Request                      $request,
        ReadOnlyModeServiceInterface $readOnlyModeService,
    ): RedirectResponse|SymfonyRedirectResponse {
        $this->redirectTo = $request->get('redirect', '/');

        $region = $request->get('region', GameServerRegion::DEFAULT_REGION);
        // An explicit allowlist rather than a table-existence check: `world` is a region row that
        // Battle.net cannot authenticate against, and any future non-OAuth region row would
        // otherwise pass the check and build an OAuth URL nobody can log in through (#4004)
        if (!in_array($region, GameServerRegion::BATTLE_NET_REGIONS, true)) {
            abort(404);
        }

        Provider::setRegion($region);

        return parent::redirectToProvider($request, $readOnlyModeService);
    }
}
