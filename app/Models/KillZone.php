<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $floor_id
 * @property string $color
 * @property double $lat
 * @property double $lng
 *
 * @property \App\Models\DungeonRoute $dungeonroute
 * @property \App\Models\Floor $floor
 *
 * @property \Illuminate\Support\Collection $enemies
 * @property \Illuminate\Support\Collection $killzoneenemies
 *
 * @mixin \Eloquent
 */
class KillZone extends Model
{
    public $visible = ['id', 'lat', 'lng', 'color', 'killzoneenemies'];
    public $with = ['killzoneenemies'];

    /**
     * Get the dungeon route that this killzone is attached to.
     *
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

    public static function boot()
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item KillZone */
            $item->deleteEnemies();
        });
    }
}
