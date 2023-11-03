<?php

namespace App\Models;

use App\Models\Floor\Floor;
use App\Models\Interfaces\ConvertsVerticesInterface;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Traits\HasVertices;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int                $id
 * @property int                $mapping_version_id
 * @property int                $floor_id
 * @property int                $group
 * @property string             $teeming
 * @property string             $faction
 * @property string|null        $color
 * @property string|null        $color_animated
 * @property string             $label
 * @property string             $vertices_json
 *
 * @property Floor              $floor
 * @property Collection|Enemy[] $enemies
 *
 * @mixin Eloquent
 */
class EnemyPack extends CacheModel implements MappingModelInterface, MappingModelCloneableInterface, ConvertsVerticesInterface
{
    use CloneForNewMappingVersionNoRelations;
    use HasVertices;

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

    protected $casts = [
        'mapping_version_id' => 'integer',
        'floor_id'           => 'integer',
    ];

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return HasMany
     */
    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    /**
     * @param string $seasonalType
     *
     * @return Collection|Enemy[]
     */
    public function getEnemiesWithSeasonalType(string $seasonalType): Collection
    {
        return $this->enemies()->where('seasonal_type', $seasonalType)->get();
    }

    /**
     * @return int|null
     */
    public function getDungeonId(): ?int
    {
        return optional($this->floor)->dungeon_id ?? null;
    }
}
