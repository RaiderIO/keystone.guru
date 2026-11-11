<?php

namespace App\Models\Patreon;

use App\Models\User;
use Closure;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int    $id
 * @property int    $giver_user_id
 * @property int    $receiver_user_id
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class PatreonAdFreeGiveaway extends Model
{
    protected $fillable = [
        'giver_user_id',
        'receiver_user_id',
    ];

    /** @return HasOne<User, $this> */
    public function giver(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'giver_user_id');
    }

    /** @return HasOne<User, $this> */
    public function receiver(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'receiver_user_id');
    }

    public static function getCountLeft(User $user): int
    {
        return $user->hasPatreonBenefit(PatreonBenefit::AD_FREE_TEAM_MEMBERS) ?
            max(0, config('keystoneguru.patreon.ad_free_giveaways') - PatreonAdFreeGiveaway::where('giver_user_id', $user->id)->count()) :
            0;
    }

    /**
     * Runs $createGiveaway in a transaction holding row locks on the giver and the receiver, so concurrent requests
     * cannot both pass the giver's giveaway limit or both hand the receiver a giveaway.
     *
     * @param Closure(): PatreonAdFreeGiveaway $createGiveaway
     */
    public static function createWhileLocked(User $giver, User $receiver, Closure $createGiveaway): PatreonAdFreeGiveaway
    {
        return DB::transaction(static function () use ($giver, $receiver, $createGiveaway): PatreonAdFreeGiveaway {
            // Must be the transaction's first read: under REPEATABLE READ the snapshot is taken at the first plain read
            User::query()
                ->whereKey([$giver->id, $receiver->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            return $createGiveaway();
        }, 3);
    }
}
