<?php

namespace App\Service\User;

/**
 * The scopes an OAuth access token for API v1 can carry. Role-gated endpoints (`api_role:...`) still check the
 * user's role on top of the scope, so a scope alone never grants admin access.
 */
enum ApiTokenScope: string
{
    case RoutesRead     = 'routes:read';
    case RoutesWrite    = 'routes:write';
    case CombatLogRead  = 'combatlog:read';
    case CombatLogWrite = 'combatlog:write';
    case Admin          = 'admin';

    /**
     * Passport resolves its scope list once at boot, which under Octane is outside any request's locale - so it
     * only gets the ids, and every page that shows a scope translates it itself through description().
     *
     * @return array<string, string> Scope id => scope id, the shape Passport::tokensCan() takes
     */
    public static function passportScopes(): array
    {
        $ids = array_column(self::cases(), 'value');

        return array_combine($ids, $ids);
    }

    public function description(): string
    {
        return __(sprintf('apitokenscopes.%s', $this->value));
    }
}
