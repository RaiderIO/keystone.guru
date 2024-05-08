<?php

namespace App\Features;

use App\Models\User;
use Illuminate\Support\Lottery;

class Heatmap
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(User $user): mixed
    {
        return $user->hasRole('');
    }
}
