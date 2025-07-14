<?php

namespace App\Models\Mapping;

use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\GameVersion\GameVersion;
use App\Models\Interfaces\ConvertsVerticesInterface;
use App\Models\MapIcon;
use App\Models\MountableArea;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Traits\SeederModel;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int                                  $id
 * @property int                                  $game_version_id
 * @property int                                  $dungeon_id
 * @property int                                  $version
 * @property int                                  $enemy_forces_required The amount of total enemy forces required to complete the dungeon.
 * @property int                                  $enemy_forces_required_teeming The amount of total enemy forces required to complete the dungeon when Teeming is enabled.
 * @property int                                  $enemy_forces_shrouded The amount of enemy forces a regular Shrouded enemy gives in this dungeon.
 * @property int                                  $enemy_forces_shrouded_zul_gamux The amount of enemy forces the Zul'gamux Shrouded enemy gives in this dungeon.
 * @property int                                  $timer_max_seconds The maximum timer (in seconds) that you have to complete the dungeon.
 * @property string|null                          $mdt_mapping_hash
 * @property bool                                 $facade_enabled True if this mapping version uses facades, false if it does not.
 * @property bool                                 $merged Not saved in the database
 *
 * @property Carbon                               $updated_at
 * @property Carbon                               $created_at
 *
 * @property GameVersion                          $gameVersion
 * @property Dungeon                              $dungeon
 *
 * @property Collection<DungeonRoute>             $dungeonRoutes
 * @property Collection<DungeonFloorSwitchMarker> $dungeonFloorSwitchMarkers
 * @property Collection<Enemy>                    $enemies
 * @property Collection<EnemyPack>                $enemyPacks
 * @property Collection<EnemyPatrol>              $enemyPatrols
 * @property Collection<MapIcon>                  $mapIcons
 * @property Collection<MountableArea>            $mountableAreas
 * @property Collection<FloorUnion>               $floorUnions
 * @property Collection<FloorUnionArea>           $floorUnionAreas
 * @property Collection<NpcEnemyForces>           $npcEnemyForces
 *
 * @mixin Eloquent
 */
class MappingVersion extends Model
{
    use HasFactory;
    use SeederModel;

    protected $visible = [
        'id',
        'game_version_id',
        'dungeon_id',
        'version',
        'enemy_forces_required',
        'enemy_forces_required_teeming',
        'enemy_forces_shrouded',
        'enemy_forces_shrouded_zul_gamux',
        'timer_max_seconds',
        'facade_enabled',
        'mdt_mapping_hash',
        'merged',
    ];

    protected $fillable = [
        'game_version_id',
        'dungeon_id',
        'version',
        'enemy_forces_required',
        'enemy_forces_required_teeming',
        'enemy_forces_shrouded',
        'enemy_forces_shrouded_zul_gamux',
        'timer_max_seconds',
        'facade_enabled',
        'mdt_mapping_hash',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'game_version_id'                 => 'integer',
        'dungeon_id'                      => 'integer',
        'version'                         => 'integer',
        'enemy_forces_required'           => 'integer',
        'enemy_forces_required_teeming'   => 'integer',
        'enemy_forces_shrouded'           => 'integer',
        'enemy_forces_shrouded_zul_gamux' => 'integer',
        'timer_max_seconds'               => 'integer',
        'facade_enabled'                  => 'integer',
    ];

    protected $appends = [
        'merged',
    ];

    protected $with = [
        'gameVersion',
//        'dungeon',
    ];

    public $timestamps = true;

    private ?Collection $cachedFloorUnionsOnFloor = null;

    private ?Collection $cachedFloorUnionsForFloor = null;

    private ?int $isLatestForDungeonCache = null;

    public function getMergedAttribute(): bool
    {
        $mostRecentlyMergedMappingCommitLog = MappingCommitLog::where('merged', 1)->orderBy('id', 'desc')->first();

        return $mostRecentlyMergedMappingCommitLog !== null && $mostRecentlyMergedMappingCommitLog->created_at->gte($this->created_at);
    }

    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    public function dungeonRoutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }

    public function dungeonFloorSwitchMarkers(): HasMany
    {
        return $this->hasMany(DungeonFloorSwitchMarker::class);
    }

    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class)->orderBy('id');
    }

    public function enemyPacks(): HasMany
    {
        return $this->hasMany(EnemyPack::class);
    }

    public function enemyPatrols(): HasMany
    {
        return $this->hasMany(EnemyPatrol::class);
    }

    public function mapIcons(): HasMany
    {
        return $this->hasMany(MapIcon::class)->whereNotNull('mapping_version_id');
    }

    public function mountableAreas(): HasMany
    {
        return $this->hasMany(MountableArea::class);
    }

    public function floorUnions(): HasMany
    {
        return $this->hasMany(FloorUnion::class);
    }

    public function floorUnionAreas(): HasMany
    {
        return $this->hasMany(FloorUnionArea::class);
    }

    public function npcEnemyForces(): HasMany
    {
        return $this->hasMany(NpcEnemyForces::class);
    }

    public function isLatestForDungeon(): bool
    {
        if ($this->isLatestForDungeonCache === null) {
            $this->isLatestForDungeonCache = MappingVersion::query()
                    ->where('dungeon_id', $this->dungeon_id)
                    ->max('version') === $this->version;
        }

        return $this->isLatestForDungeonCache;
    }

    public function getPrettyName(): string
    {
        $this->load([
            'dungeon' => fn(BelongsTo $query) => $query->without('mappingVersions'),
        ]);

        return sprintf('%s: %s Version %d (%s%d, %s)',
            __($this->gameVersion->name),
            __($this->dungeon->name),
            $this->version,
            $this->merged ? 'readonly, ' : '',
            $this->id,
            $this->created_at
        );
    }

    public function getTimerUpgradePlusTwoSeconds(): int
    {
        return $this->timer_max_seconds * config('keystoneguru.keystone.timer.plustwofactor');
    }

    public function getTimerUpgradePlusThreeSeconds(): int
    {
        return $this->timer_max_seconds * config('keystoneguru.keystone.timer.plusthreefactor');
    }

    public function getMapIconNearLocation(LatLng $latLng, int $mapIconTypeId): ?MapIcon
    {
        $range = 5;
        /** @var MapIcon|null $mapIcon */
        $mapIcon = $this->mapIcons()
            ->where('lat', '>', $latLng->getLat() - $range)
            ->where('lat', '<', $latLng->getLat() + $range)
            ->where('lng', '>', $latLng->getLng() - $range)
            ->where('lng', '<', $latLng->getLng() + $range)
            ->where('map_icon_type_id', $mapIconTypeId)
            ->first();

        return $mapIcon;
    }

    /**
     * @return Collection<FloorUnion>
     */
    public function getFloorUnionsOnFloor(int $floorId): Collection
    {
        if ($this->cachedFloorUnionsOnFloor === null) {
            $this->cachedFloorUnionsOnFloor = collect();
        }

        if ($this->cachedFloorUnionsOnFloor->has($floorId)) {
            return $this->cachedFloorUnionsOnFloor->get($floorId);
        }

        $floorUnions = $this
            ->floorUnions()
            ->where('floor_id', $floorId)
            ->with(['floor', 'targetFloor'])
            ->get();

        $this->cachedFloorUnionsOnFloor->put($floorId, $floorUnions);

        return $floorUnions;
    }

    public function getFloorUnionForLatLng(CoordinatesServiceInterface $coordinatesService, MappingVersion $mappingVersion, LatLng $latLng): ?FloorUnion
    {
        $floor = $latLng->getFloor();
        if ($floor === null) {
            return null;
        }

        if ($this->cachedFloorUnionsForFloor === null) {
            $this->cachedFloorUnionsForFloor = collect();
        }

        /** @var Collection<FloorUnion> $floorUnions */
        if ($this->cachedFloorUnionsForFloor->has($floor->id)) {
            $floorUnions = $this->cachedFloorUnionsForFloor->get($floor->id);
        } else {
            $floorUnions = $this->floorUnions()
                ->where('target_floor_id', $floor->id)
                ->with(['floor', 'targetFloor'])
                ->get();

            $this->cachedFloorUnionsForFloor->put($floor->id, $floorUnions);
        }

        // Now that we know the floor union candidates, check which floor union we need to use
        $result = null;
        // If we have more than 1 target we must make a choice based on the floor union areas attached to the floor union
        if ($floorUnions->count() > 1) {
            foreach ($floorUnions as $floorUnion) {
                // We need to translate the target point using this floor union first, prior to checking the floor union areas
                // Only if the translated point falls in the floor union area, can we properly check if this floor union matches
                $tmpConvertedLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $latLng, $floorUnion);
                foreach ($floorUnion->floorUnionAreas as $floorUnionArea) {
                    if ($floorUnionArea->containsPoint($coordinatesService, $tmpConvertedLatLng)) {
                        $result = $floorUnion;
                        break;
                    }
                }
            }
        } else {
            $result = $floorUnions->first();
        }

        return $result;
    }

    /**
     * @return Collection<Enemy>
     */
    public function mapContextEnemies(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<Enemy> $enemies */
        $enemies = $this->enemies()
            ->with(['floor'])
            ->without(['npc'])
            ->get()
            ->makeHidden(['enemy_active_auras']);

        if ($this->facade_enabled && $useFacade) {
            foreach ($enemies as $enemy) {
                $convertedLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this,
                    $enemy->getLatLng()
                );

                $enemy->setLatLng($convertedLatLng);
            }
        }

        return $enemies;
    }

    /**
     * @todo duplicated function in DungeonRoute.php
     */
    private function convertVerticesForFacade(
        CoordinatesServiceInterface $coordinatesService,
        ConvertsVerticesInterface   $hasVertices,
        Floor                       $floor
    ): Floor {
        $convertedLatLngs = collect();

        foreach ($hasVertices->getDecodedLatLngs($floor) as $latLng) {
            $convertedLatLngs->push($coordinatesService->convertMapLocationToFacadeMapLocation(
                $this,
                $latLng
            ));
        }

        $newFloor = isset($convertedLatLngs[0]) ? $convertedLatLngs[0]->getFloor() : $floor;

        $hasVertices->vertices_json = json_encode($convertedLatLngs->map(static fn(LatLng $latLng) => $latLng->toArray()));

        return $newFloor;
    }

    /**
     * @return Collection<EnemyPack>
     */
    public function mapContextEnemyPacks(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<EnemyPack> $enemyPacks */
        $enemyPacks = $this->enemyPacks()->with(['floor', 'enemies:enemies.id,enemies.enemy_pack_id'])->get();

        if ($this->facade_enabled && $useFacade) {
            $enemyPacks = $enemyPacks->map(function (EnemyPack $enemyPack) use ($coordinatesService) {
                $newFloor = $this->convertVerticesForFacade($coordinatesService, $enemyPack, $enemyPack->floor);
                $enemyPack->setRelation('floor', $newFloor);
                $enemyPack->floor_id = $newFloor->id;

                return $enemyPack;
            });
        }

        return $enemyPacks;
    }

    /**
     * @return Collection<EnemyPatrol>
     */
    public function mapContextEnemyPatrols(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<EnemyPatrol> $enemyPatrols */
        $enemyPatrols = $this->enemyPatrols()->with('floor')->get();

        if ($this->facade_enabled && $useFacade) {
            $enemyPatrols = $enemyPatrols->map(function (EnemyPatrol $enemyPatrol) use ($coordinatesService) {
                $newFloor = $this->convertVerticesForFacade($coordinatesService, $enemyPatrol->polyline, $enemyPatrol->floor);
                $enemyPatrol->setRelation('floor', $newFloor);
                $enemyPatrol->floor_id = $newFloor->id;

                return $enemyPatrol;
            });
        }

        return $enemyPatrols;
    }

    /**
     * @return Collection<MapIcon>
     */
    public function mapContextMapIcons(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<MapIcon> $mapIcons */
        $mapIcons = $this->mapIcons()
            ->with(['floor'])
            ->get();

        if ($this->facade_enabled && $useFacade) {
            foreach ($mapIcons as $mapIcon) {
                $convertedLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this,
                    $mapIcon->getLatLng()
                );

                $mapIcon->setLatLng($convertedLatLng);
            }
        }

        return $mapIcons;
    }

    /**
     * @return Collection<DungeonFloorSwitchMarker>
     */
    public function mapContextDungeonFloorSwitchMarkers(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<DungeonFloorSwitchMarker> $dungeonFloorSwitchMarkers */
        $dungeonFloorSwitchMarkers = $this->dungeonFloorSwitchMarkers()
            ->whereNull('source_floor_id')
            ->with('floor')
            ->get();

        if ($this->facade_enabled && $useFacade) {
            foreach ($dungeonFloorSwitchMarkers as $dungeonFloorSwitchMarker) {
                // Load some attributes prior to changing the floor_id, otherwise they get messed up
                $dungeonFloorSwitchMarker->setAttribute('source_floor_id', $dungeonFloorSwitchMarker->floor_id);
                $dungeonFloorSwitchMarker->setAttribute('floorCouplingDirection', $dungeonFloorSwitchMarker->getFloorCouplingDirectionAttribute());

                $convertedLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this,
                    $dungeonFloorSwitchMarker->getLatLng()
                );

                $dungeonFloorSwitchMarker->setLatLng($convertedLatLng);
            }
        }

        return $dungeonFloorSwitchMarkers;
    }

    /**
     * @return Collection<MountableArea>
     */
    public function mapContextMountableAreas(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<MountableArea> $mountableAreas */
        $mountableAreas = $this->mountableAreas()->with('floor')->get();

        if ($this->facade_enabled && $useFacade) {
            $mountableAreas = $mountableAreas->map(function (MountableArea $mountableArea) use ($coordinatesService) {
                $newFloor = $this->convertVerticesForFacade($coordinatesService, $mountableArea, $mountableArea->floor);
                $mountableArea->setRelation('floor', $newFloor);
                $mountableArea->floor_id = $newFloor->id;

                return $mountableArea;
            });
        }

        return $mountableAreas;
    }

    public function mapContextFloorUnions(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        return $this->floorUnions;
    }

    public function mapContextFloorUnionAreas(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        return $this->floorUnionAreas;
    }

    protected static function boot()
    {
        parent::boot();

        // If we create a new mapping version, we must create a complete copy of the previous mapping and re-save that to the database.
        static::created(static function (MappingVersion $newMappingVersion) {
            if ($newMappingVersion->dungeon === null) {
                return;
            }
            /** @var Collection<MappingVersion> $existingMappingVersions */
            $existingMappingVersions = $newMappingVersion->dungeon->mappingVersions()->get();
            // Nothing to do if we don't have an older mapping version
            if ($existingMappingVersions->count() < 2) {
                return;
            }
            // We must get the previous mapping version - that contains the mapping we want to clone
            $previousMappingVersion = $existingMappingVersions[1];
            // Update the existing fields of the old mapping version to the new version
            $newMappingVersion->update([
                'enemy_forces_required'           => $previousMappingVersion->enemy_forces_required,
                'enemy_forces_required_teeming'   => $previousMappingVersion->enemy_forces_required_teeming,
                'enemy_forces_shrouded'           => $previousMappingVersion->enemy_forces_shrouded,
                'enemy_forces_shrouded_zul_gamux' => $previousMappingVersion->enemy_forces_shrouded_zul_gamux,
                'timer_max_seconds'               => $previousMappingVersion->timer_max_seconds,
            ]);
            $previousMappingVersion->load([
                'dungeonFloorSwitchMarkers',
                'enemies',
                'enemyPacks',
                'enemyPatrols',
                'mapIcons',
                'mountableAreas',
                'floorUnions',
                'floorUnionAreas',
                'npcEnemyForces',
            ]);
            /** @var Collection<MappingModelInterface> $previousMapping */
            $previousMapping = collect()
                ->merge($previousMappingVersion->dungeonFloorSwitchMarkers)
                ->merge($previousMappingVersion->enemies)
                ->merge($previousMappingVersion->enemyPacks)
                ->merge($previousMappingVersion->enemyPatrols)
                ->merge($previousMappingVersion->mapIcons)
                ->merge($previousMappingVersion->mountableAreas)
                ->merge($previousMappingVersion->floorUnions)
                ->merge($previousMappingVersion->floorUnionAreas)
                ->merge($previousMappingVersion->npcEnemyForces);
            $idMapping       = collect([
                DungeonFloorSwitchMarker::class => collect(),
                Enemy::class                    => collect(),
                EnemyPack::class                => collect(),
                EnemyPatrol::class              => collect(),
                MapIcon::class                  => collect(),
                MountableArea::class            => collect(),
                FloorUnion::class               => collect(),
                FloorUnionArea::class           => collect(),
                NpcEnemyForces::class           => collect(),
            ]);

            // Take the giant list of models and re-save them one by one for the new version of the mapping
            foreach ($previousMapping as $model) {
                /** @var CloneForNewMappingVersionNoRelations $model */
                $newModel = $model->cloneForNewMappingVersion($newMappingVersion);

                /** @var Collection $modelMapping */
                $modelMapping = $idMapping->get($model::class);
                $modelMapping->push([
                    'oldModel' => $model,
                    'newModel' => $newModel,
                ]);
            }
            // Change enemy packs of new enemies
            foreach ($idMapping->get(Enemy::class) as $enemyRelationCoupling) {
                /** @var array{oldModel: Enemy, newModel: Enemy} $enemyRelationCoupling */
                $oldEnemyPackId = $enemyRelationCoupling['oldModel']->enemy_pack_id;

                // Find the new ID of the pack
                foreach ($idMapping->get(EnemyPack::class) as $enemyPackRelationCoupling) {
                    /** @var array{oldModel: EnemyPack, newModel: EnemyPack} $enemyPackRelationCoupling */
                    if ($enemyPackRelationCoupling['oldModel']->id === $oldEnemyPackId) {
                        $enemyRelationCoupling['newModel']->enemy_pack_id = $enemyPackRelationCoupling['newModel']->id;
                        $enemyRelationCoupling['newModel']->save();
                        break;
                    }
                }

                $oldEnemyPatrolId = $enemyRelationCoupling['oldModel']->enemy_patrol_id;
                if ($oldEnemyPatrolId !== null) {
                    // Find the new ID of the enemy patrol
                    foreach ($idMapping->get(EnemyPatrol::class) as $enemyPatrolRelationCoupling) {
                        /** @var array{oldModel: EnemyPatrol, newModel: EnemyPatrol} $enemyPatrolRelationCoupling */
                        if ($enemyPatrolRelationCoupling['oldModel']->id === $oldEnemyPatrolId) {
                            $enemyRelationCoupling['newModel']->update([
                                'enemy_patrol_id' => $enemyPatrolRelationCoupling['newModel']->id,
                            ]);
                            break;
                        }
                    }
                }
            }
            // Change floor unions of floor union areas
            foreach ($idMapping->get(FloorUnionArea::class) as $floorUnionAreaRelationCoupling) {
                /** @var array{oldModel: FloorUnionArea, newModel: FloorUnionArea} $floorUnionAreaRelationCoupling */
                $oldFloorUnionId = $floorUnionAreaRelationCoupling['oldModel']->floor_union_id;

                // Find the new ID of the floor union
                foreach ($idMapping->get(FloorUnion::class) as $floorUnionRelationCoupling) {
                    /** @var array{oldModel: FloorUnion, newModel: FloorUnion} $floorUnionRelationCoupling */
                    if ($floorUnionRelationCoupling['oldModel']->id === $oldFloorUnionId) {
                        $floorUnionAreaRelationCoupling['newModel']->update([
                            'floor_union_id' => $floorUnionRelationCoupling['newModel']->id,
                        ]);
                        break;
                    }
                }
            }
        });

        // Deleting a mapping version also causes their relations to be deleted (as does creating a mapping version duplicates them)
        static::deleting(static function (MappingVersion $mappingVersion) {
            $mappingVersion->dungeonFloorSwitchMarkers()->delete();
            $mappingVersion->enemies()->delete();
            foreach ($mappingVersion->enemyPacks as $enemyPack) {
                $enemyPack->delete();
            }

            foreach ($mappingVersion->enemyPatrols as $enemyPatrol) {
                $enemyPatrol->delete();
            }

            $mappingVersion->mapIcons()->delete();
            $mappingVersion->mountableAreas()->delete();
            $mappingVersion->floorUnions()->delete();
            $mappingVersion->floorUnionAreas()->delete();
            $mappingVersion->npcEnemyForces()->delete();
        });
    }
}
