<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\User;

class Heatmap
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

        $env = config('app.env');

        // Ok, feature is enabled, now check if YOU can do it
        // If we're local AND open search is setup, then we can show heatmaps
        return (($env === 'local' && !empty(config('opensearch-laravel.host'))) || $env !== 'local');
    }
}
