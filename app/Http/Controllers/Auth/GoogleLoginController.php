<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;

class GoogleLoginController extends OAuthLoginController
{
    protected function getUser($oauthUser, $oAuthId): User
    {
        return new User([
            'public_key' => User::generateRandomPublicKey(),
            'oauth_id' => $oAuthId,
            // Prefer nickname over full name
            'name' => $oauthUser->nickname ?? $oauthUser->name,
            'email' => $oauthUser->email,
            'echo_color' => randomHexColor(),
            'password' => '',
            'legal_agreed' => 1,
            'legal_agreed_ms' => -1,
        ]);
    }

    protected function getDriver(): string
    {
        return 'google';
    }
}
