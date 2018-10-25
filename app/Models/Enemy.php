<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property int $enemy_pack_id
 * @property int $npc_id
 * @property int $floor_id
 * @property bool $is_infested
 * @property string $teeming
 * @property string $faction
 * @property string $enemy_forces_override
 * @property double $lat
 * @property double $lng
 * @property \App\Models\EnemyPack $enemyPack
 * @property \App\Models\Npc $npc
 * @property \App\Models\Floor $floor
 * @property \Illuminate\Support\Collection $vertices
 * @property \Illuminate\Support\Collection $thisweeksinfestedvotes
 */
class Enemy extends Model
{
    public $with = ['npc'];
    public $hidden = ['npc_id'];
    public $timestamps = false;
    public $appends = ['user_infested_vote', 'is_infested'];

    /**
     * Checks if this enemy is Infested or not.
     * @return bool
     */
    function getUserInfestedVoteAttribute()
    {
        $result = -1;

        if (Auth::check()) {
            $first = $this->thisweeksinfestedvotes->where('user_id', Auth::user()->id)->first();
            if ($first !== null) {
                $result = $first->vote;
            }
        }

        return $result;
    }

    /**
     * Checks if this enemy is Infested or not.
     * @return bool
     */
    function getIsInfestedAttribute()
    {
        $yesVotes = $this->getInfestedYesVotesCount();;
        $noVotes = $this->getInfestedNoVotesCount();

        return ($yesVotes - $noVotes) >= config('keystoneguru.infested_user_vote_threshold');
    }

    /**
     * Get the amount of yes votes of people saying this enemy should be infested.
     * @return int
     */
    function getInfestedYesVotesCount()
    {
        return $this->thisweeksinfestedvotes->where('vote', true)->count();
    }


    /**
     * Get the amount of no votes of people saying this enemy should NOT be infested.
     * @return int
     */
    function getInfestedNoVotesCount()
    {
        return $this->thisweeksinfestedvotes->where('vote', false)->count();
    }

    /**
     * Gets the infested votes for this enemy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function thisweeksinfestedvotes()
    {
        $region = null;
        if (Auth::check()) {
            $region = Auth::user()->gameserverregion;
        }

        return $this->hasMany('App\Models\EnemyInfestedVote')
            ->where('affix_group_id', $region->getCurrentAffixGroup()->id)
            // Only votes that are made less than a month ago to prevent counting votes from previous cycle
            ->where('updated_at', '>', Carbon::now()->subMonth()->format('Y-m-d H:i:s'));
    }

    /**
     * Gets the infested votes for this enemy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function infestedvotes()
    {
        return $this->hasMany('App\Models\EnemyInfestedVote');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function pack()
    {
        return $this->belongsTo('App\Models\EnemyPack');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function npc()
    {
        return $this->belongsTo('App\Models\Npc');
    }
}
