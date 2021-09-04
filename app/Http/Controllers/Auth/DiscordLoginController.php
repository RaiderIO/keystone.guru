<?php

namespace App\Http\Controllers\Auth;

use App\User;

class DiscordLoginController extends OAuthLoginController
{
    protected function getUser($oauthUser, $oAuthId)
    {
        return new User([
            'oauth_id'        => $oAuthId,
            // Prefer nickname over full name
            'name'            => $oauthUser->nickname,
            'email'           => $oauthUser->email !== null ? $oauthUser->email : sprintf('%s@discordapp.com', $oauthUser->id),
            'echo_color'      => randomHexColor(),
            'password'        => '',
            'legal_agreed'    => 1,
            'legal_agreed_ms' => -1,
        ]);
    }


    protected function getDriver()
    {
        return 'discord';
    }
}
