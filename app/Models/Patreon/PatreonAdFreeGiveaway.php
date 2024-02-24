<?php

namespace App\Models\Patreon;

use App\User;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int    $id
 * @property int    $giver_user_id
 * @property int    $receiver_user_id
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class PatreonAdFreeGiveaway extends Model
{
    protected $fillable = ['giver_user_id', 'receiver_user_id'];

    /**
     * @return HasOne
     */
    public function giver(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'giver_user_id');
    }

    /**
     * @return HasOne
     */
    public function receiver(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'receiver_user_id');
    }

    /**
     * @return int
     */
    public static function getCountLeft(User $user): int
    {
        return $user->hasPatreonBenefit(PatreonBenefit::AD_FREE_TEAM_MEMBERS) ?
            max(0, config('keystoneguru.patreon.ad_free_giveaways') - PatreonAdFreeGiveaway::where('giver_user_id', $user->id)->count()) :
            0;
    }
}
