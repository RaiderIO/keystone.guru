<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\User;

class NpcCompendium
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(?User $user): bool
    {
        // The admin switch is the only gate - once enabled, it's live for everyone, including guests
        return Feature::getAdminValue(self::class);
    }
}
