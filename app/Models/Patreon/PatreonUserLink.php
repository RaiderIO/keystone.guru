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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int                                   $id
 * @property int                                   $user_id
 * @property string                                $email
 * @property string                                $scope
 * @property string                                $access_token
 * @property string                                $refresh_token
 * @property string                                $version
 * @property string                                $expires_at
 * @property Carbon|null                           $last_seen_at     Last time the hourly sync actually saw this link's Patreon member
 * @property ApplyPaidBenefitsForMemberResult|null $last_sync_result What that sync decided for them
 * @property Carbon                                $created_at
 * @property User|null                             $user             Null for a link orphaned by a user deletion: User::deleting only
 *                                                                   removes one link via HasOne::first(), so any duplicate row survives
 *
 * @property PatreonManualGrant|null $activeManualGrant
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

    protected $with = ['patreonBenefits'];

    protected $visible = [
        'patreonBenefits',
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

    /**
     * A link is manually granted either because it was fabricated wholesale by the admin panel for a
     * user with no Patreon account at all (the permanent token), or because the user has a real link
     * that an active grant overrides. Both cases mean the benefits are not being paid for.
     */
    public function getManuallyGrantedAttribute(): bool
    {
        return $this->refresh_token === self::PERMANENT_TOKEN || $this->activeManualGrant !== null;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The grant is keyed on the user rather than on the link, so that revoking an override does not
     * have to touch patreon_user_links - whose expires_at column is ON UPDATE CURRENT_TIMESTAMP and
     * would silently expire the link on any write. The link carries user_id itself, so this stays a
     * single relation rather than a hop back through the user.
     *
     * @return HasOne<PatreonManualGrant, $this>
     */
    public function activeManualGrant(): HasOne
    {
        return $this->hasOne(PatreonManualGrant::class, 'user_id', 'user_id')->whereNull('revoked_at');
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
