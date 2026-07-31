<?php

namespace App\Models;

use App\Models\Floor\Floor;
use App\Models\Interfaces\HasLatLngInterface;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasLatLng;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * A mapper-defined group of enemies - typically a corridor or a wing - whose combined enemy forces
 * tell you how much you must already have killed before entering it. The group is not bound to a
 * single floor; only the anchor point the pill is drawn at is.
 *
 * @property int         $id
 * @property int         $mapping_version_id
 * @property int         $floor_id
 * @property string|null $name
 * @property float       $lat
 * @property float       $lng
 *
 * @property MappingVersion         $mappingVersion
 * @property Floor                  $floor
 * @property Collection<int, Enemy> $enemies
 *
 * @mixin Eloquent
 */
class EnemyForcesCheckpoint extends CacheModel implements HasLatLngInterface, MappingModelCloneableInterface, MappingModelInterface
{
    use CloneForNewMappingVersionNoRelations;
    use HasLatLng;
    use SeederModel;

    protected $hidden = [
        'mappingVersion',
        'floor',
        'laravel_through_key',
    ];

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'name',
        'lat',
        'lng',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'mapping_version_id' => 'integer',
            'floor_id'           => 'integer',
            'lat'                => 'float',
            'lng'                => 'float',
        ];
    }

    /**
     * @return BelongsTo<MappingVersion, $this>
     */
    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    /**
     * @return BelongsTo<Floor, $this>
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * The enemies that make up this checkpoint. They may live on any floor of the dungeon - not just the
     * floor this checkpoint is anchored on.
     *
     * @return HasMany<Enemy, $this>
     */
    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    public function getDungeonId(): ?int
    {
        return $this->floor->dungeon_id;
    }

    /**
     * Registered as `deleted` rather than `deleting`: the members only need detaching once the checkpoint is
     * really gone.
     */
    #[Override]
    protected static function booted(): void
    {
        // There are no foreign key constraints in this application, so members would otherwise keep
        // pointing at a checkpoint that no longer exists - and the next checkpoint to be handed this
        // auto-increment id would silently inherit them, reporting enemy forces for enemies nobody
        // ever assigned to it.
        static::deleted(static function (EnemyForcesCheckpoint $enemyForcesCheckpoint) {
            Enemy::query()
                ->where('enemy_forces_checkpoint_id', $enemyForcesCheckpoint->id)
                ->update(['enemy_forces_checkpoint_id' => null]);
        });
    }
}
