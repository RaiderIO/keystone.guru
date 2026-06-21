<?php

namespace App\Models\KillZone;

use App\Logic\Structs\LatLng;
use App\Models\Affix;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Spell\Spell;
use App\Models\Traits\HasLatLng;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * @property int        $id
 * @property int        $dungeon_route_id
 * @property int|null   $floor_id
 * @property string     $color
 * @property string     $description
 * @property int        $index
 * @property float|null $lat
 * @property float|null $lng
 *
 * @property DungeonRoute|null                      $dungeonRoute
 * @property Floor                                  $floor
 * @property EloquentCollection<int, Enemy>         $enemies         Enemy models via BelongsToMany; serialized to integer IDs in toArray()
 * @property EloquentCollection<int, KillZoneEnemy> $killZoneEnemies
 * @property EloquentCollection<int, KillZoneSpell> $killZoneSpells
 * @property EloquentCollection<int, Spell>         $spells
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class KillZone extends Model
{
    use HasLatLng;
    /** @use HasFactory<\Database\Factories\KillZone\KillZoneFactory> */
    use HasFactory;

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
        'killzone_paths',
    ];

    protected $with = ['spells:id,icon_name'];

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

    private ?Floor $dominantFloorCache = null;

    protected function casts(): array
    {
        return [
            'dungeon_route_id' => 'integer',
            'floor_id'         => 'integer',
            'index'            => 'integer',
            'lat'              => 'float',
            'lng'              => 'float',
        ];
    }

    /**
     * Get the dungeon route that this killzone is attached to.
     *
     * @return BelongsTo<DungeonRoute, $this>
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /** @return HasMany<KillZoneEnemy, $this> */
    public function killZoneEnemies(): HasMany
    {
        return $this->hasMany(KillZoneEnemy::class);
    }

    /** @return HasMany<KillZoneSpell, $this> */
    public function killZoneSpells(): HasMany
    {
        return $this->hasMany(KillZoneSpell::class);
    }

    /** @return BelongsToMany<Enemy, $this> */
    public function enemies(): BelongsToMany
    {
        return $this->belongsToMany(Enemy::class, 'kill_zone_enemies', 'kill_zone_id', 'enemy_id');
    }

    /** @return BelongsToMany<Spell, $this> */
    public function spells(): BelongsToMany
    {
        return $this->belongsToMany(Spell::class, 'kill_zone_spells');
    }

    /** @return BelongsTo<Floor, $this> */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function hasKillArea(): bool
    {
        return $this->floor_id !== null && $this->lat !== null && $this->lng !== null;
    }

    /**
     * @return EloquentCollection<int, Enemy>
     */
    public function getEnemies(): EloquentCollection
    {
        $this->loadMissing('enemies.npc');

        return $this->getRelation('enemies');
    }

    /**
     * The floor that we have a killzone on, or the floor that contains the most enemies (and thus most dominant floor)
     */
    public function getDominantFloor(bool $useCache = false): ?Floor
    {
        if ($useCache && $this->dominantFloorCache instanceof Floor) {
            return $this->dominantFloorCache;
        }

        $result = null;

        if (isset($this->floor_id) && $this->floor_id > 0) {
            $result = $this->floor;
        }

        if ($result === null && (
            $this->relationLoaded('enemies') ? $this->getRelation('enemies')->isNotEmpty() : (
                $this->relationLoaded('killZoneEnemies') ?
                    $this->killZoneEnemies->isNotEmpty() :
                    $this->killZoneEnemies()->exists()
            )
        )) {
            $floorTotals = [];
            foreach ($this->getEnemies() as $enemy) {
                if (!isset($floorTotals[$enemy->floor_id])) {
                    $floorTotals[$enemy->floor_id] = 0;
                }

                $floorTotals[$enemy->floor_id]++;
            }

            if (!empty($floorTotals)) {
                // Will get a random floor if there's equal counts on multiple floors, that's ok
                $floorId = array_search(max($floorTotals), $floorTotals, true);

                $result = Floor::findOrFail($floorId);
            }
        }

        return $this->dominantFloorCache = $result;
    }

    public function getKillLocation(bool $useCache = false): ?LatLng
    {
        if (isset($this->lat) && isset($this->lng)) {
            return new LatLng($this->lat, $this->lng, $this->getDominantFloor(true));
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

            return new LatLng($totalLat / $enemies->count(), $totalLng / $enemies->count(), $this->getDominantFloor(true));
        }
    }

    /**
     * @return array<string, float|int>|null The coordinates of a rectangle that perfectly fits all enemies inside this pull.
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
     */
    public function getEnemiesBoundingBoxNorthEdgeMiddleCoordinate(int $boundingBoxMargin): ?LatLng
    {
        $boundingBox = $this->getEnemiesBoundingBox($boundingBoxMargin);
        if ($boundingBox === null) {
            return null;
        }

        return new LatLng(
            $boundingBox['latMax'],
            $boundingBox['lngMin'] + (($boundingBox['lngMax'] - $boundingBox['lngMin']) / 2),
            $this->getDominantFloor(true),
        );
    }

    /**
     * Gets a list of enemy forces that this kill zone kills that may be skipped.
     *
     * @return Collection<int, \stdClass>
     */
    public function getSkippableEnemyForces(bool $teeming): Collection
    {
        $isShrouded = $this->dungeonRoute->getSeasonalAffix()?->key === Affix::AFFIX_SHROUDED;

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
                 left join `enemies` on `enemies`.`npc_id` = `kill_zone_enemies`.`npc_id` AND `enemies`.`mdt_id` = `kill_zone_enemies`.`mdt_id`
                    {$ifIsShroudedJoins}
            where kill_zones.id = :kill_zone_id
              and enemies.mapping_version_id = dungeon_routes.mapping_version_id
              and enemies.skippable = 1
            group by kill_zone_enemies.id, enemies.enemy_pack_id
            ", [
            'teeming'      => (int)$teeming,
            'kill_zone_id' => $this->id,
        ]);

        return collect($queryResult);
    }

    /**
     * Serialize enemies as an array of integer IDs for the frontend instead of full model objects.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(): array
    {
        $array = parent::toArray();

        if (isset($array['enemies'])) {
            $array['enemies'] = array_column($array['enemies'], 'id');
        }

        return $array;
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // Delete kill zone properly if it gets deleted
        static::deleting(static function (KillZone $item) {
            $item->killZoneEnemies()->delete();
            $item->killZoneSpells()->delete();
        });
    }
}
