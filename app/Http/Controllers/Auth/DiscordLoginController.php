<?php

namespace App\Http\Controllers\Auth;

use App\User;

class DiscordLoginController extends OAuthLoginController
{
    protected function getUser($oauthUser, $oAuthId): User
    {
        return new User([
            'public_key'      => User::generateRandomPublicKey(),
            'oauth_id'        => $oAuthId,
            // Prefer nickname over full name
            'name'            => $oauthUser->nickname,
            'email'           => $oauthUser->email ?? sprintf('%s@discordapp.com', $oauthUser->id),
            'echo_color'      => randomHexColor(),
            'password'        => '',
            'legal_agreed'    => 1,
            'legal_agreed_ms' => -1,
        ]);
    }

    protected function getDriver(): string
    {
        return 'discord';
    }
}
