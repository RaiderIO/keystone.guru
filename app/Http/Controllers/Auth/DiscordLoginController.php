<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;

class DiscordLoginController extends OAuthLoginController
{
    /**
     * @param object $oauthUser
     * @param mixed  $oAuthId
     */
    protected function getUser($oauthUser, $oAuthId): User
    {
        return new User([
            'public_key' => User::generateRandomPublicKey(),
            'oauth_id'   => $oAuthId,
            // Prefer nickname over full name
            'name'         => $oauthUser->nickname,
            'email'        => $oauthUser->email ?? sprintf('%s@discordapp.com', $oauthUser->id),
            'echo_color'   => randomHexColor(),
            'password'     => '',
            'legal_agreed' => 1,
        ]);
    }

    protected function getDriver(): string
    {
        return 'discord';
    }

    /**
     * Discord returns the account's e-mail address whether or not its owner confirmed it; only the
     * raw `verified` flag says which.
     *
     * @param object $oauthUser
     */
    #[\Override]
    protected function hasUnverifiedEmail($oauthUser): bool
    {
        if ($oauthUser->email === null) {
            return false;
        }

        return ($oauthUser->user['verified'] ?? false) !== true;
    }
}
