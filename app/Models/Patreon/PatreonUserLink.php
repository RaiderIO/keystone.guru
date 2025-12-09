<?php

namespace App\Models\Patreon;

use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $email
 * @property string                         scope
 * @property string $access_token
 * @property string $refresh_token
 * @property string $version
 * @property string $expires_at
 * @property User   $user
 *
 * @property Collection<PatreonUserBenefit> $patreonUserBenefits
 * @property Collection<PatreonBenefit>     $patreonBenefits
 *
 * @mixin Eloquent
 */
class PatreonUserLink extends Model
{
    public const PERMANENT_TOKEN = 'grantedthroughadminpages';

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

    protected $visible = [
        'patreonbenefits',
        'manually_granted',
    ];

    protected $appends = ['manually_granted'];

    public function getManuallyGrantedAttribute(): bool
    {
        return $this->refresh_token === self::PERMANENT_TOKEN;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patreonUserBenefits(): HasMany
    {
        return $this->hasMany(PatreonUserBenefit::class);
    }

    public function patreonBenefits(): BelongsToMany
    {
        return $this->belongsToMany(PatreonBenefit::class, 'patreon_user_benefits');
    }

    public function isExpired(): bool
    {
        return Carbon::createFromTimeString($this->expires_at)->isPast();
    }

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(static function (PatreonUserLink $item) {
            $item->patreonUserBenefits()->delete();
        });
    }
}
