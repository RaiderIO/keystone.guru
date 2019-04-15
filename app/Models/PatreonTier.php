<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $patreon_data_id int
 * @property $paid_tier_id int
 * @property User $user
 *
 * @mixin \Eloquent
 */
class PatreonTier extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function patreondata()
    {
        return $this->belongsTo('App\Models\PatreonData');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function tiers()
    {
        return $this->hasMany('App\Models\PatreonTier');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    function paidtiers()
    {
        return $this->belongsToMany('App\Models\PaidTier', 'patreon_tiers');
    }
}
