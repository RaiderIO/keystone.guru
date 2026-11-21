<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
use Laravel\Pennant\Feature as Pennant;

/**
 * The map layer that draws the walk graph: where players walked, built from combat logs.
 *
 * While the layer is being checked against real graphs this resolves only for admins, and only once an admin has
 * switched it on.
 */
class WalkGraphLayer
{
    /** Every walked segment, shaded by how many runs walked it */
    public const string STYLE_SEGMENTS = 'segments';

    /** The outline of the area players walk through */
    public const string STYLE_AREA = 'area';

    /**
     * Resolve the feature's initial value.
     */
    public function resolve(?User $user): bool
    {
        // If the admin can't do it, we have disabled it entirely. So you can't do it either
        if (!Feature::getAdminValue(self::class)) {
            return false;
        }

        // Ok, feature is enabled, now check if YOU can do it
        return $user?->hasRole(Role::ROLE_ADMIN) ?? false;
    }

    /**
     * How a map draws the walk graph layer: the page's style when it opted in and the feature is active for the
     * current user, otherwise null and the map does not offer the layer.
     *
     * @param self::STYLE_*|null $pageWalkGraphLayerStyle
     *
     * @return self::STYLE_*|null
     */
    public static function getStyleForMap(?string $pageWalkGraphLayerStyle): ?string
    {
        return $pageWalkGraphLayerStyle !== null && Pennant::active(self::class) ? $pageWalkGraphLayerStyle : null;
    }
}
