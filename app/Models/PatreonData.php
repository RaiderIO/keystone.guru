<?php

namespace App\Models;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property $id int
 * @property $user_id int
 * @property $access_token string
 * @property $refresh_token string
 * @property $expires_at datetime
 *
 * @property User $user
 * @property Collection|PaidTier[] $paidtiers
 *
 * @mixin Eloquent
 */
class PatreonData extends Model
{
    protected $table = 'patreon_data';
    protected $with = ['paidtiers'];
    protected $visible = ['paidtiers'];

    /**
     * @return HasOne
     */
    function user(): HasOne
    {
        return $this->hasOne('App\User');
    }

    /**
     * @return HasMany
     */
    function tiers(): HasMany
    {
        return $this->hasMany('App\Models\PatreonTier');
    }

    /**
     * @return BelongsToMany
     */
    function paidtiers(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\PaidTier', 'patreon_tiers');
    }

    public static function boot()
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(function (PatreonData $item) {
            $item->tiers()->delete();
        });
    }
}
