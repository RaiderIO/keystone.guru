<?php

namespace App\Models\KillZone;

use App\Models\Affix;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\Spell;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property int                        $id
 * @property int                        $dungeon_route_id
 * @property int                        $floor_id
 * @property string                     $color
 * @property string                     $description
 * @property int                        $index
 * @property double                     $lat
 * @property double                     $lng
 *
 * @property DungeonRoute               $dungeonRoute
 * @property Floor                      $floor
 *
 * @property Collection|int[]           $enemies
 * @property Collection|KillZoneEnemy[] $killZoneEnemies
 * @property Collection|KillZoneSpell[] $killZoneSpells
 *
 * @property Carbon                     $updated_at
 * @property Carbon                     $created_at
 *
 * @mixin Eloquent
 */
class KillZone extends Model
{
    public $visible = [
        'id',
        'floor_id',
        'color',
        'description',
        'lat',
        'lng',
        'index',
        'enemies',
        'spells',
    ];

    protected $appends = ['enemies'];
    protected $with = ['spells:id'];

    protected $fillable = [
        'id',
        'dungeon_route_id',
        'floor_id',
        'color',
        'description',
        'index',
        'lat',
        'lng',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'floor_id' => 'integer',
        'index'    => 'integer',
        'lat'      => 'float',
        'lng'      => 'float',
    ];

    private Collection $enemiesCache;

    /**
     * @param Collection $enemyIds
     * @return void
     */
    public function setEnemiesCache(Collection $enemyIds): void
    {
        $this->enemiesCache = $enemyIds;
    }

    /**
     * @return Collection
     */
    public function getEnemiesAttribute(): Collection
    {
        return $this->enemiesCache ?? Enemy::select('enemies.id')
            ->join('kill_zone_enemies', function (JoinClause $clause) {
                $clause->on('kill_zone_enemies.npc_id', DB::raw('coalesce(enemies.mdt_npc_id, enemies.npc_id)'))
                    ->on('kill_zone_enemies.mdt_id', 'enemies.mdt_id');
            })
            ->join('kill_zones', 'kill_zones.id', 'kill_zone_enemies.kill_zone_id')
            ->join('dungeon_routes', 'dungeon_routes.id', 'kill_zones.dungeon_route_id')
            ->whereColumn('enemies.mapping_version_id', 'dungeon_routes.mapping_version_id')
            ->where('kill_zone_enemies.kill_zone_id', $this->id)
            // Disabling model caching makes this query work - not sure why the cache would break it, but it does
            ->disableCache()
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
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return HasMany
     */
    public function killZoneEnemies(): HasMany
    {
        return $this->hasMany(KillZoneEnemy::class);
    }

    /**
     * @return HasMany
     */
    public function killZoneSpells(): HasMany
    {
        return $this->hasMany(KillZoneSpell::class);
    }

    /**
     * @return BelongsToMany
     */
    public function spells(): BelongsToMany
    {
        return $this->belongsToMany(Spell::class, 'kill_zone_spells');
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
        } else if ($this->killZoneEnemies()->count() > 0) {
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
     * @return array|null The coordinates of a rectangle that perfectly fits all enemies inside this pull.
     */
    public function getEnemiesBoundingBox(int $margin = 0): ?array
    {
        $enemies = $this->getEnemies();

        if ($enemies->isEmpty()) {
            return null;
        }

        $result = [
            'latMin' => 999999,
            'latMax' => -999999,
            'lngMin' => 999999,
            'lngMax' => -999999,
        ];

        foreach ($enemies as $enemy) {
            if ($result['latMin'] > $enemy->lat) {
                $result['latMin'] = $enemy->lat;
            }
            if ($result['latMax'] < $enemy->lat) {
                $result['latMax'] = $enemy->lat;
            }

            if ($result['lngMin'] > $enemy->lng) {
                $result['lngMin'] = $enemy->lng;
            }
            if ($result['lngMax'] < $enemy->lng) {
                $result['lngMax'] = $enemy->lng;
            }
        }

        if ($margin > 0) {
            $result['latMin'] -= $margin;
            $result['latMax'] += $margin;
            $result['lngMin'] -= $margin;
            $result['lngMax'] += $margin;
        }

        return $result;
    }

    /**
     * Calculate the bounding box of all enemies that this pull kills, take the north edge of that bounding box
     * and return the middle of that edge as a lat/lng.
     *
     * @param int $boundingBoxMargin
     * @return array|null
     */
    public function getEnemiesBoundingBoxNorthEdgeMiddleCoordinate(int $boundingBoxMargin): ?array
    {
        $boundingBox = $this->getEnemiesBoundingBox($boundingBoxMargin);
        if ($boundingBox === null) {
            return null;
        }

        return [
            // Max lat is at the top
            'lat' => $boundingBox['latMax'],
            'lng' => $boundingBox['lngMin'] + (($boundingBox['lngMax'] - $boundingBox['lngMin']) / 2),
        ];
    }

    /**
     * Gets a list of enemy forces that this kill zone kills that may be skipped.
     *
     * @param bool $teeming
     * @return Collection
     */
    public function getSkippableEnemyForces(bool $teeming): Collection
    {
        $isShrouded = $this->dungeonRoute->getSeasonalAffix() === Affix::AFFIX_SHROUDED;

        // Ignore the shrouded query if we're not shrouded (make it fail)
        $ifIsShroudedEnemyForcesQuery = $isShrouded ? '
            IF(
                enemies.seasonal_type = "shrouded",
                mapping_versions.enemy_forces_shrouded,
                IF(
                    enemies.seasonal_type = "shrouded_zul_gamux",
                    mapping_versions.enemy_forces_shrouded_zul_gamux,
                    npc_enemy_forces.enemy_forces
                )
            )
        ' : 'npc_enemy_forces.enemy_forces';

        $ifIsShroudedJoins = $isShrouded ? '
                left join `mapping_versions` on `mapping_versions`.`id` = `dungeon_routes`.`mapping_version_id`
            ' : '';

        $queryResult = DB::select("
            select `kill_zone_enemies`.*,
                    enemies.id as enemy_id,
                    enemies.enemy_pack_id,
                   CAST(IFNULL(
                           IF(:teeming = 1,
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override_teeming IS NOT NULL,
                                              enemies.enemy_forces_override_teeming,
                                              IF(
                                                  npc_enemy_forces.enemy_forces_teeming IS NOT NULL,
                                                  npc_enemy_forces.enemy_forces_teeming,
                                                  {$ifIsShroudedEnemyForcesQuery}
                                                  )
                                          )
                                  ),
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override IS NOT NULL,
                                              enemies.enemy_forces_override,
                                              {$ifIsShroudedEnemyForcesQuery}
                                          )
                                  )
                               ), 0
                       ) AS SIGNED) as enemy_forces
            from `kill_zone_enemies`
                 left join `kill_zones` on `kill_zones`.`id` = `kill_zone_enemies`.`kill_zone_id`
                 left join `dungeon_routes` on `dungeon_routes`.`id` = `kill_zones`.`dungeon_route_id`
                 left join `dungeons` on `dungeons`.`id` = `dungeon_routes`.`dungeon_id`
                 left join `npcs` on `npcs`.`id` = `kill_zone_enemies`.`npc_id`
                 left join `npc_enemy_forces` on `npcs`.`id` = `npc_enemy_forces`.`npc_id` AND `dungeon_routes`.`mapping_version_id` = `npc_enemy_forces`.`mapping_version_id`
                 left join `enemies` on `enemies`.`id` = `kill_zone_enemies`.`enemy_id`
                    {$ifIsShroudedJoins}
            where kill_zones.id = :kill_zone_id
              and enemies.mapping_version_id = dungeon_routes.mapping_version_id
              and enemies.skippable = 1
            group by kill_zone_enemies.id, enemies.enemy_pack_id
            ", ['teeming' => (int)$teeming, 'kill_zone_id' => $this->id]);

        return collect($queryResult);
    }

    public static function boot()
    {
        parent::boot();

        // Delete kill zone properly if it gets deleted
        static::deleting(function (KillZone $item) {
            $item->killZoneEnemies()->delete();
        });
    }
}
