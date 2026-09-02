<?php

namespace App\Models\Patreon;

use App\Models\User;
use Database\Factories\Patreon\PatreonManualGrantFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An audit record of an admin manually granting a user all Patreon benefits. An active (not revoked)
 * grant makes the hourly Patreon sync leave the user's benefits alone, so it acts as an override of
 * whatever tier the user actually pays for - see PatreonService::applyPaidBenefitsForMember().
 *
 * @property int         $id
 * @property int         $user_id
 * @property int|null    $granted_by_user_id
 * @property string      $reason
 * @property Carbon|null $revoked_at
 * @property int|null    $revoked_by_user_id
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 *
 * @property User      $user
 * @property User|null $grantedBy
 * @property User|null $revokedBy
 *
 * @mixin Eloquent
 */
class PatreonManualGrant extends Model
{
    /** @use HasFactory<PatreonManualGrantFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'granted_by_user_id',
        'reason',
        'revoked_at',
        'revoked_by_user_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    /**
     * @param Builder<PatreonManualGrant> $query
     *
     * @return Builder<PatreonManualGrant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }
}
