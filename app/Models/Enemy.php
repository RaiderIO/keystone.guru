<?php

namespace App\Models;

use App\Models\Traits\Reportable;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $enemy_pack_id
 * @property int $npc_id
 * @property int $floor_id
 * @property int $mdt_id The ID in MDT (clone index) that this enemy is coupled to
 * @property int $seasonal_type The type of of seasonal effect this enemy has. Awakened to signify an Awakened enemy, Inspiring to signify an Inspiring enemy
 * @property int $seasonal_index Shows/hides this enemy based on the seasonal index as defined in Affix Group. If they match, the enemy is shown, otherwise hidden. If not set enemy is always shown.
 * @property int $mdt_npc_index The index of the NPC in MDT (not saved in DB)
 * @property int $enemy_id Only used for temp MDT enemies (not saved in DB)
 * @property bool $is_mdt Only used for temp MDT enemies (not saved in DB)
 * @property string $teeming
 * @property string $faction
 * @property boolean $unskippable
 * @property boolean $skippable
 * @property string $enemy_forces_override
 * @property string $enemy_forces_override_teeming
 * @property double $lat
 * @property double $lng
 *
 * @property EnemyPack $enemyPack
 * @property Npc $npc
 * @property Floor $floor
 *
 * @property EnemyActiveAura[]|Collection $enemyactiveauras
 *
 * @mixin Eloquent
 */
class Enemy extends CacheModel
{
    use Reportable;

    public $appends = ['active_auras'];
    public $with = ['npc', 'enemyactiveauras'];
    public $hidden = ['laravel_through_key'];
    public $timestamps = false;

    /**
     * @return array
     */
    function getActiveAurasAttribute(){
        $result = [];

        foreach($this->enemyactiveauras as $activeaura){
            $result[] = $activeaura->spell_id;
        }

        return $result;
    }

    /**
     * @return BelongsTo
     */
    function pack()
    {
        return $this->belongsTo('App\Models\EnemyPack');
    }

    /**
     * @return BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * @return BelongsTo
     */
    function npc()
    {
        return $this->belongsTo('App\Models\Npc');
    }

    /**
     * @return HasMany
     */
    function enemyactiveauras()
    {
        return $this->hasMany('App\Models\EnemyActiveAura');
    }
}
