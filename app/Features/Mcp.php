<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * Lets a user connect their own AI client (Claude Code, Claude Desktop, claude.ai connectors, ...) to the MCP server
 * at /mcp and build routes through it. Connecting also takes an OAuth grant, so the ApiTokens feature must be active
 * for the user as well.
 *
 * While the MCP server is being tried on production, this resolves only for admins, and only once an admin has
 * switched it on.
 */
class Mcp
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(?User $user): bool
    {
        // If the admin can't do it, we have disabled it entirely. So you can't do it either
        if (!Feature::getAdminValue(self::class)) {
            return false;
        }

        return $user?->hasRole(Role::ROLE_ADMIN) ?? false;
    }
}
