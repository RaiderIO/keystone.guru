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
    function dungeonroute(): BelongsTo
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return BelongsToMany
     */
    function enemies(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Enemy', 'kill_zone_enemies');
    }

    /**
     * @return HasMany
     */
    function killzoneenemies(): HasMany
    {
        return $this->hasMany('App\Models\KillZoneEnemy');
    }

    /**
     * @return BelongsTo
     */
    function floor(): BelongsTo
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * The floor that we have a killzone on, or the floor that contains the most enemies (and thus most dominant floor)
     * @return Floor
     */
    public function getDominantFloor(): ?Floor
    {
        if (isset($this->floor_id)) {
            return $this->floor;
        } else if ($this->enemies->count() > 0) {
            $floorTotals = [];
            foreach ($this->enemies as $enemy) {
                if (!isset($floorTotals[$enemy->floor_id])) {
                    $floorTotals[$enemy->floor_id] = 0;
                }
                $floorTotals[$enemy->floor_id]++;
            }

            // Will get a random floor if there's equal counts on multiple floors, that's ok
            $floorId = array_search(max($floorTotals), $floorTotals);

            return Floor::findOrFail($floorId);
        } else {
            return null;
        }
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function getKillLocation(): array
    {
        if (isset($this->lat) && isset($this->lng)) {
            return ['lat' => $this->lat, 'lng' => $this->lng];
        } else {
            $totalLng = 0;
            $totalLat = 0;

            foreach ($this->enemies as $enemy) {
                $totalLat += $enemy->lat;
                $totalLng += $enemy->lng;
            }

            return ['lat' => $totalLat / $this->enemies->count(), 'lng' => $totalLng / $this->enemies->count()];
        }
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

    public static function boot()
    {
        parent::boot();

        // Delete kill zone properly if it gets deleted
        static::deleting(function (KillZone $item) {
            $item->killzoneenemies()->delete();
        });
    }
}
