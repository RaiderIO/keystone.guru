<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $floor_id
 * @property string $color
 * @property int $index
 * @property double $lat
 * @property double $lng
 *
 * @property DungeonRoute $dungeonroute
 * @property Floor $floor
 *
 * @property Collection|Enemy[] $enemies
 * @property Collection|KillZoneEnemy[] $killzoneenemies
 *
 * @mixin Eloquent
 */
class KillZone extends Model
{
    public $visible = ['id', 'floor_id', 'color', 'lat', 'lng', 'index', 'killzoneenemies'];
    public $with = ['killzoneenemies'];

    /**
     * Get the dungeon route that this killzone is attached to.
     *
     * @return BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return BelongsToMany
     */
    function enemies()
    {
        return $this->belongsToMany('App\Models\Enemy', 'kill_zone_enemies');
    }

    /**
     * @return HasMany
     */
    function killzoneenemies()
    {
        return $this->hasMany('App\Models\KillZoneEnemy');
    }

    /**
     * @return BelongsTo
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

        $this->unsetRelation('killzoneenemies');
    }

    public static function boot()
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(function ($item)
        {
            /** @var $item KillZone */
            $item->deleteEnemies();
        });
    }
}
