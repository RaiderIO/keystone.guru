<?php

namespace App\Models\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\MapIcon;
use App\Models\MountableArea;
use App\Models\Npc\NpcEnemyForces;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $version
 * @property int $enemy_forces_required The amount of total enemy forces required to complete the dungeon.
 * @property int $enemy_forces_required_teeming The amount of total enemy forces required to complete the dungeon when Teeming is enabled.
 * @property int $enemy_forces_shrouded The amount of enemy forces a regular Shrouded enemy gives in this dungeon.
 * @property int $enemy_forces_shrouded_zul_gamux The amount of enemy forces the Zul'gamux Shrouded enemy gives in this dungeon.
 * @property int $timer_max_seconds The maximum timer (in seconds) that you have to complete the dungeon.
 * @property string|null $mdt_mapping_hash
 * @property bool $merged Not saved in the database
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property Dungeon $dungeon
 * @property Collection|DungeonFloorSwitchMarker[] $dungeonFloorSwitchMarkers
 * @property Collection|Enemy[] $enemies
 * @property Collection|EnemyPack[] $enemyPacks
 * @property Collection|EnemyPatrol[] $enemyPatrols
 * @property Collection|MapIcon[] $mapIcons
 * @property Collection|MountableArea[] $mountableAreas
 * @property Collection|NpcEnemyForces[] $npcEnemyForces
 *
 * @mixin Eloquent
 */
class MappingVersion extends Model
{
    protected $visible = [
        'id',
        'dungeon_id',
        'version',
        'enemy_forces_required',
        'enemy_forces_required_teeming',
        'enemy_forces_shrouded',
        'enemy_forces_shrouded_zul_gamux',
        'timer_max_seconds',
        'mdt_mapping_hash',
        'merged',
    ];

    protected $fillable = [
        'dungeon_id',
        'version',
        'enemy_forces_required',
        'enemy_forces_required_teeming',
        'enemy_forces_shrouded',
        'enemy_forces_shrouded_zul_gamux',
        'timer_max_seconds',
        'mdt_mapping_hash',
        'updated_at',
        'created_at',
    ];

    protected $appends = [
        'merged',
    ];

    public $timestamps = true;

    /**
     * @return bool
     */
    public function getMergedAttribute(): bool
    {
        $mostRecentlyMergedMappingCommitLog = MappingCommitLog::where('merged', 1)->orderBy('id', 'desc')->first();

        return $mostRecentlyMergedMappingCommitLog !== null && $mostRecentlyMergedMappingCommitLog->created_at->gte($this->created_at);
    }


    /**
     * @return BelongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /**
     * @return HasMany
     */
    public function dungeonFloorSwitchMarkers(): HasMany
    {
        return $this->hasMany(DungeonFloorSwitchMarker::class);
    }

    /**
     * @return HasMany
     */
    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class)->orderBy('id');
    }

    /**
     * @return HasMany
     */
    public function enemyPacks(): HasMany
    {
        return $this->hasMany(EnemyPack::class);
    }

    /**
     * @return HasMany
     */
    public function enemyPatrols(): HasMany
    {
        return $this->hasMany(EnemyPatrol::class);
    }

    /**
     * @return HasMany
     */
    public function mapIcons(): HasMany
    {
        return $this->hasMany(MapIcon::class);
    }

    /**
     * @return HasMany
     */
    public function mountableAreas(): HasMany
    {
        return $this->hasMany(MountableArea::class);
    }

    /**
     * @return HasMany
     */
    public function npcEnemyForces(): HasMany
    {
        return $this->hasMany(NpcEnemyForces::class);
    }

    /**
     * @return bool
     */
    public function isLatestForDungeon(): bool
    {
        return $this->dungeon->getCurrentMappingVersion()->version === $this->version;
    }

    /**
     * @return string
     */
    public function getPrettyName(): string
    {
        return sprintf('%s Version %d (%s%d, %s)',
            __($this->dungeon->name),
            $this->version,
            $this->merged ? 'readonly, ' : '',
            $this->id,
            $this->created_at
        );
    }


    public static function boot()
    {
        parent::boot();

        // If we create a new mapping version, we must create a complete copy of the previous mapping and re-save that to the database.
        static::created(function (MappingVersion $newMappingVersion) {
            /** @var Collection|MappingVersion[] $existingMappingVersions */
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

            /** @var Collection|MappingModelInterface[] $previousMapping */
            $previousMapping = collect()
                ->merge($previousMappingVersion->dungeonFloorSwitchMarkers)
                ->merge($previousMappingVersion->enemies)
                ->merge($previousMappingVersion->enemyPacks)
                ->merge($previousMappingVersion->enemyPatrols)
                ->merge($previousMappingVersion->mapIcons)
                ->merge($previousMappingVersion->mountableAreas)
                ->merge($previousMappingVersion->npcEnemyForces);

            $idMapping = collect([
                DungeonFloorSwitchMarker::class => collect(),
                Enemy::class                    => collect(),
                EnemyPack::class                => collect(),
                EnemyPatrol::class              => collect(),
                MapIcon::class                  => collect(),
                MountableArea::class            => collect(),
                NpcEnemyForces::class           => collect(),
            ]);

            // Take the giant list of models and re-save them one by one for the new version of the mapping
            foreach ($previousMapping as $model) {
                /** @var CloneForNewMappingVersionNoRelations $model */
                $newModel = $model->cloneForNewMappingVersion($newMappingVersion);

                $idMapping->get(get_class($model))->push([
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
                            $enemyRelationCoupling['newModel']->enemy_patrol_id = $enemyPatrolRelationCoupling['newModel']->id;
                            $enemyRelationCoupling['newModel']->save();
                            break;
                        }
                    }
                }
            }
        });

        // Deleting a mapping version also causes their relations to be deleted (as does creating a mapping version duplicates them)
        static::deleting(function (MappingVersion $mappingVersion) {
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
            $mappingVersion->npcEnemyForces()->delete();
        });
    }
}
