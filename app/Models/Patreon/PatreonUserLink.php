<?php

namespace App\Models\Patreon;

use App\Models\User;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use Database\Factories\Patreon\PatreonUserLinkFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $email
 * @property string $scope
 * @property string $access_token
 * @property string $refresh_token
 * @property string $version
 * @property string $expires_at
 * @property Carbon|null $last_seen_at     Last time the hourly sync actually saw this link's Patreon member
 * @property ApplyPaidBenefitsForMemberResult|null $last_sync_result What that sync decided for them
 * @property User   $user
 *
 * @property EloquentCollection<int, PatreonUserBenefit> $patreonUserBenefits
 * @property EloquentCollection<int, PatreonBenefit>     $patreonBenefits
 *
 * @mixin Eloquent
 */
class PatreonUserLink extends Model
{
    /** @use HasFactory<PatreonUserLinkFactory> */
    use HasFactory;

    public const string PERMANENT_TOKEN = 'grantedthroughadminpages';

    protected $fillable = [
        'user_id',
        'email',
        'scope',
        'access_token',
        'refresh_token',
        'version',
        'expires_at',
        'last_seen_at',
        'last_sync_result',
    ];

    protected $with = ['patreonbenefits'];

    protected $visible = [
        'patreonbenefits',
        'manually_granted',
    ];

    protected $appends = ['manually_granted'];

    protected function casts(): array
    {
        return [
            'last_seen_at'     => 'datetime',
            'last_sync_result' => ApplyPaidBenefitsForMemberResult::class,
        ];
    }

    public function getManuallyGrantedAttribute(): bool
    {
        return $this->refresh_token === self::PERMANENT_TOKEN;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PatreonUserBenefit, $this> */
    public function patreonUserBenefits(): HasMany
    {
        return $this->hasMany(PatreonUserBenefit::class);
    }

    /** @return BelongsToMany<PatreonBenefit, $this> */
    public function patreonBenefits(): BelongsToMany
    {
        return $this->belongsToMany(PatreonBenefit::class, 'patreon_user_benefits');
    }

    public function isExpired(): bool
    {
        return Carbon::createFromTimeString($this->expires_at)->isPast();
    }

    #[Override]
    protected static function boot()
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(static function (PatreonUserLink $item) {
            $item->patreonUserBenefits()->delete();
        });
    }
}
