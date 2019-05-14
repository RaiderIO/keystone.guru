<?php

namespace App\Http\Controllers\Auth;

use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleLoginController extends OAuthLoginController
{
    protected function getEmailAddress($oauthUser)
    {
        return $oauthUser->email;
    }

    protected function getUser($oauthUser, $oAuthId, $email)
    {
        return new User([
            'oauth_id' => $oAuthId,
            // Prefer nickname over full name
            'name' => isset($oauthUser->nickname) && $oauthUser->nickname !== null ? $oauthUser->nickname : $oauthUser->name,
            'email' => $email,
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
