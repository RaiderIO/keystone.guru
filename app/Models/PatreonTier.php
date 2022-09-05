<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property $id int
 * @property $patreon_data_id int
 * @property $paid_tier_id int
 *
 * @mixin Eloquent
 */
class PatreonTier extends Model
{
    protected $fillable = ['patreon_data_id', 'paid_tier_id'];

    /**
     * @return BelongsTo
     */
    function patreondata(): BelongsTo
    {
        return $this->belongsTo(PatreonData::class);
    }

    /**
     * @return HasMany
     */
    function tiers(): HasMany
    {
        return $this->hasMany(PatreonTier::class);
    }

    /**
     * @return BelongsToMany
     */
    function paidtiers(): BelongsToMany
    {
        return $this->belongsToMany(PaidTier::class, 'patreon_tiers');
    }
}
