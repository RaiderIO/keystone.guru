<?php

namespace App\Models;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property string $access_token
 * @property string $refresh_token
 * @property string $expires_at
 *
 * @property User $user
 * @property Collection|PaidTier[] $paidtiers
 *
 * @mixin Eloquent
 */
class PatreonData extends Model
{
    protected $table = 'patreon_data';
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];
    protected $with = ['paidtiers'];
    protected $visible = ['paidtiers'];

    /**
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * @return HasMany
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(PatreonTier::class);
    }

    /**
     * @return BelongsToMany
     */
    public function paidtiers(): BelongsToMany
    {
        return $this->belongsToMany(PaidTier::class, 'patreon_tiers');
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return Carbon::createFromTimeString($this->expires_at)->isPast();
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
