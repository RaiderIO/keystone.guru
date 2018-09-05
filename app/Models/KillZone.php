<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $floor_id
 * @property double $lat
 * @property double $lng
 * @property \App\Models\DungeonRoute $dungeonroute
 * @property \App\Models\Floor $floor
 * @property \Illuminate\Support\Collection $enemies
 */
class KillZone extends Model
{
    public $hidden = ['dungeon_route_id'];
    public $with = ['dungeonroute', 'killzoneenemies'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function enemies()
    {
        return $this->belongsToMany('App\Models\Enemy', 'kill_zone_enemies');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function killzoneenemies()
    {
        return $this->hasMany('App\Models\KillZoneEnemy');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * Deletes all enemies that are related to this Route.
     */
    function deleteEnemies()
    {
        // Load the existing kill zone enemies
        $existingKillZoneEnemiesIds = $this->killzoneenemies->pluck('id')->all();
        // Only if there's enemies to destroy
        if (count($existingKillZoneEnemiesIds) > 0) {
            // Kill them off
            KillZoneEnemy::destroy($existingKillZoneEnemiesIds);
        }
    }
}
