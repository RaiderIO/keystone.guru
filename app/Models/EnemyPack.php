<?php

namespace App\Models;

use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $mapping_version_id
 * @property int $floor_id
 * @property string $teeming
 * @property string $faction
 * @property string|null $color
 * @property string|null $color_animated
 * @property string $label
 * @property string $vertices_json
 *
 * @property Floor $floor
 * @property Collection|Enemy[] $enemies
 *
 * @mixin Eloquent
 */
class EnemyPack extends CacheModel implements MappingModelInterface, MappingModelCloneableInterface
{
    use CloneForNewMappingVersionNoRelations;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'floor_id',
        'teeming',
        'faction',
        'color',
        'color_animated',
        'label',
        'vertices_json',
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
     * @return Collection
     */
    public function getEnemiesWithSeasonalType(string $seasonalType): Collection
    {
        return $this->enemies()->where('seasonal_type', $seasonalType)->get();
    }

    /**
     * @return int
     */
    public function getDungeonId(): int
    {
        return $this->floor->dungeon_id;
    }
}
