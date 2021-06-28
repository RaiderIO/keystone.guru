<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class KillZone extends Model
{
    public $visible = ['id', 'floor_id', 'color', 'lat', 'lng', 'index', 'killzoneenemies'];
    public $with = ['killzoneenemies'];
    protected $fillable = ['id', 'dungeon_route_id', 'floor_id', 'color', 'index', 'lat', 'lng', 'updated_at', 'created_at'];

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
     * Gets a list of enemy forces per enemy that this kill zone kills.
     * @param bool $teeming
     * @return Collection
     */
    public function getSkippableEnemyForces(bool $teeming): Collection
    {
        $queryResult = DB::select('
            select `kill_zone_enemies`.*,
                    enemies.enemy_pack_id,
                   CAST(IFNULL(
                           IF(:teeming = 1,
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override_teeming >= 0,
                                              enemies.enemy_forces_override_teeming,
                                              IF(npcs.enemy_forces_teeming >= 0, npcs.enemy_forces_teeming, npcs.enemy_forces)
                                          )
                                  ),
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override >= 0,
                                              enemies.enemy_forces_override,
                                              npcs.enemy_forces
                                          )
                                  )
                               ), 0
                       ) AS SIGNED) as enemy_forces
            from `kill_zone_enemies`
                 left join `kill_zones` on `kill_zones`.`id` = `kill_zone_enemies`.`kill_zone_id`
                 left join `enemies` on `enemies`.`id` = `kill_zone_enemies`.`enemy_id`
                 left join `npcs` on `npcs`.`id` = `enemies`.`npc_id`
            where kill_zones.id = :kill_zone_id
            and enemies.skippable = 1
            group by kill_zone_enemies.id, enemies.enemy_pack_id
            ', ['teeming' => (int)$teeming, 'kill_zone_id' => $this->id]);

        return collect($queryResult);
    }

    /**
     * Deletes all enemies that are related to this Route.
     */
    public function deleteEnemies()
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
