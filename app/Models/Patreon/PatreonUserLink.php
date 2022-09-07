<?php

namespace App\Models\Patreon;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string scope
 * @property string $access_token
 * @property string $refresh_token
 * @property string $version
 * @property string $expires_at
 *
 * @property User $user
 * @property Collection|PatreonBenefit[] $patreonbenefits
 *
 * @mixin Eloquent
 */
class PatreonUserLink extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'scope',
        'access_token',
        'refresh_token',
        'version',
        'expires_at',
    ];
    protected $with = ['patreonbenefits'];
    protected $visible = ['patreonbenefits'];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany
     */
    public function patreonuserbenefits(): HasMany
    {
        return $this->hasMany(PatreonUserBenefit::class);
    }

    /**
     * @return BelongsToMany
     */
    public function patreonbenefits(): BelongsToMany
    {
        return $this->belongsToMany(PatreonBenefit::class, 'patreon_user_benefits');
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
        static::deleting(function (PatreonUserLink $item) {
            $item->patreonuserbenefits()->delete();
        });
    }
}
