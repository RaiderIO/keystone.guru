<?php

namespace App\Http\Controllers\Auth;

use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscordLoginController extends OAuthLoginController
{
    protected function getEmailAddress($oauthUser)
    {
        return $oauthUser->email !== null ? $oauthUser->email : sprintf('%s@discordapp.com', $oauthUser->id);
    }

    protected function createUser($oauthUser, $oAuthId, $email)
    {
        return User::create([
            'oauth_id' => $oAuthId,
            // Prefer nickname over full name
            'name' => $oauthUser->nickname,
            'email' => $email,
            'password' => '',
            'legal_agreed' => 1,
            'legal_agreed_ms' => -1
        ]);
    }


    protected function getDriver()
    {
        return 'discord';
    }
}
