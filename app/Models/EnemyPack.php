<?php

namespace App\Models;

use App\Models\Floor\Floor;
use App\Models\Interfaces\ConvertsVerticesInterface;
use App\Models\Interfaces\HasVerticesInterface;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasVertices;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int         $mapping_version_id
 * @property int         $floor_id
 * @property int         $group
 * @property string      $teeming
 * @property string      $faction
 * @property string|null $color
 * @property string|null $color_animated
 * @property string      $label
 * @property string      $vertices_json
 *
 * @property Floor                          $floor
 * @property EloquentCollection<int, Enemy> $enemies
 *
 * @mixin Eloquent
 */
class EnemyPack extends CacheModel implements HasVerticesInterface, ConvertsVerticesInterface, MappingModelCloneableInterface, MappingModelInterface
{
    use CloneForNewMappingVersionNoRelations;
    use HasVertices;
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'group',
        'teeming',
        'faction',
        'color',
        'color_animated',
        'label',
        'vertices_json',
    ];

    protected $hidden = [
        'mappingVersion',
        'floor',
    ];

    protected function casts(): array
    {
        return [
            'mapping_version_id' => 'integer',
            'floor_id'           => 'integer',
            'group'              => 'integer',
        ];
    }

    /** @return BelongsTo<MappingVersion, $this> */
    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    /** @return BelongsTo<Floor, $this> */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /** @return HasMany<Enemy, $this> */
    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    /**
     * @return EloquentCollection<int, Enemy>
     */
    public function getEnemiesWithSeasonalType(string $seasonalType): EloquentCollection
    {
        return $this->enemies()->where('seasonal_type', $seasonalType)->get();
    }

    public function getDungeonId(): ?int
    {
        return $this->floor->dungeon_id;
    }
}
