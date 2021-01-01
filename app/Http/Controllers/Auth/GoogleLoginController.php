<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Faker\Provider\Color;

class GoogleLoginController extends OAuthLoginController
{
    protected function getUser($oauthUser, $oAuthId)
    {
        return new User([
            'oauth_id' => $oAuthId,
            // Prefer nickname over full name
            'name' => isset($oauthUser->nickname) && $oauthUser->nickname !== null ? $oauthUser->nickname : $oauthUser->name,
            'email' => $oauthUser->email,
            'echo_color' => Color::hexColor(),
            'password' => '',
            'legal_agreed' => 1,
            'legal_agreed_ms' => -1
        ]);
    }


    protected function getDriver()
    {
        return 'google';
    }
}
