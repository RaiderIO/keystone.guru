<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
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
 * @property Collection|int[] $enemies
 * @property Collection|KillZoneEnemy[] $killzoneenemies
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class KillZone extends Model
{
    public $visible = ['id', 'floor_id', 'color', 'lat', 'lng', 'index', 'enemies'];
    protected $appends = ['enemies'];
    protected $fillable = ['id', 'dungeon_route_id', 'floor_id', 'color', 'index', 'lat', 'lng', 'updated_at', 'created_at'];
    protected $casts = [
        'floor_id' => 'integer',
        'index'    => 'integer',
    ];

    /**
     * @return Collection
     */
    public function getEnemiesAttribute(): Collection
    {
        return Enemy::select('enemies.id')
            ->join('kill_zone_enemies', function (JoinClause $clause) {
                $clause->on('kill_zone_enemies.npc_id', DB::raw('coalesce(enemies.mdt_npc_id, enemies.npc_id)'))
                    ->on('kill_zone_enemies.mdt_id', 'enemies.mdt_id');
            })
            ->join('kill_zones', 'kill_zones.id', 'kill_zone_enemies.kill_zone_id')
            ->join('dungeon_routes', 'dungeon_routes.id', 'kill_zones.dungeon_route_id')
            ->whereColumn('enemies.mapping_version_id', 'dungeon_routes.mapping_version_id')
            ->where('kill_zone_enemies.kill_zone_id', $this->id)
            ->get()
            ->map(function (Enemy $enemy) {
                return $enemy->id;
            });
    }

    /**
     * Get the dungeon route that this killzone is attached to.
     *
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return HasMany
     */
    public function killzoneenemies(): HasMany
    {
        return $this->hasMany(KillZoneEnemy::class);
    }

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return Collection|Enemy[]
     */
    public function getEnemies(): Collection
    {
        return Enemy::select('enemies.*')
            ->join('kill_zone_enemies', function (JoinClause $clause) {
                $clause->on('kill_zone_enemies.npc_id', 'enemies.npc_id')
                    ->on('kill_zone_enemies.mdt_id', 'enemies.mdt_id');
            })
            ->join('kill_zones', 'kill_zones.id', 'kill_zone_enemies.kill_zone_id')
            ->join('dungeon_routes', 'dungeon_routes.id', 'kill_zones.dungeon_route_id')
            ->whereColumn('enemies.mapping_version_id', 'dungeon_routes.mapping_version_id')
            ->where('kill_zone_enemies.kill_zone_id', $this->id)
            ->get();
    }

    /**
     * The floor that we have a killzone on, or the floor that contains the most enemies (and thus most dominant floor)
     * @return Floor
     */
    public function getDominantFloor(): ?Floor
    {
        if (isset($this->floor_id) && $this->floor_id > 0) {
            return $this->floor;
        } else if ($this->killzoneenemies()->count() > 0) {
            $floorTotals = [];
            foreach ($this->getEnemies() as $enemy) {
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
    public function getKillLocation(): ?array
    {
        if (isset($this->lat) && isset($this->lng)) {
            return ['lat' => $this->lat, 'lng' => $this->lng];
        } else {
            $enemies = $this->getEnemies();

            if ($enemies->isEmpty()) {
                return null;
            }

            $totalLng = 0;
            $totalLat = 0;

            foreach ($enemies as $enemy) {
                $totalLat += $enemy->lat;
                $totalLng += $enemy->lng;
            }

            return ['lat' => $totalLat / $enemies->count(), 'lng' => $totalLng / $enemies->count()];
        }
    }

    /**
     * Gets a list of enemy forces that this kill zone kills that may be skipped.
     *
     * @param bool $teeming
     * @return Collection
     */
    public function getSkippableEnemyForces(bool $teeming): Collection
    {
        $isShrouded = $this->dungeonroute->getSeasonalAffix() === Affix::AFFIX_SHROUDED;

        // Ignore the shrouded query if we're not shrouded (make it fail)
        $ifIsShroudedEnemyForcesQuery = $isShrouded ? '
            IF(
                enemies.seasonal_type = "shrouded",
                dungeons.enemy_forces_shrouded,
                IF(
                    enemies.seasonal_type = "shrouded_zul_gamux",
                    dungeons.enemy_forces_shrouded_zul_gamux,
                    npcs.enemy_forces
                )
            )
        ' : 'npcs.enemy_forces';

        $queryResult = DB::select(sprintf('
            select `kill_zone_enemies`.*,
                    enemies.id as enemy_id,
                    enemies.enemy_pack_id,
                   CAST(IFNULL(
                           IF(:teeming = 1,
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override_teeming IS NOT NULL,
                                              enemies.enemy_forces_override_teeming,
                                              IF(npcs.enemy_forces_teeming >= 0, npcs.enemy_forces_teeming, %s)
                                          )
                                  ),
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override IS NOT NULL,
                                              enemies.enemy_forces_override,
                                              %s
                                          )
                                  )
                               ), 0
                       ) AS SIGNED) as enemy_forces
            from `kill_zone_enemies`
                 left join `kill_zones` on `kill_zones`.`id` = `kill_zone_enemies`.`kill_zone_id`
                 left join `dungeon_routes` on `dungeon_routes`.`id` = `kill_zones`.`dungeon_route_id`
                 left join `dungeons` on `dungeons`.`id` = `dungeon_routes`.`dungeon_id`
                 left join `npcs` on `npcs`.`id` = `kill_zone_enemies`.`npc_id`
                 left join `enemies` on `enemies`.`id` = `kill_zone_enemies`.`enemy_id`
            where kill_zones.id = :kill_zone_id
              and enemies.mapping_version_id = dungeon_routes.mapping_version_id
              and enemies.skippable = 1
            group by kill_zone_enemies.id, enemies.enemy_pack_id
            ', $ifIsShroudedEnemyForcesQuery, $ifIsShroudedEnemyForcesQuery), ['teeming' => (int)$teeming, 'kill_zone_id' => $this->id]);

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
