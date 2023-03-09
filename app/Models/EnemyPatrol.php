<?php

namespace App\Models;

use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\hasOne;

/**
 * @property int $id
 * @property int $mapping_version_id
 * @property int $floor_id
 * @property int $polyline_id
 * @property string $teeming
 * @property string $faction
 *
 * @property Floor $floor
 * @property Polyline $polyline
 *
 * @mixin Eloquent
 */
class EnemyPatrol extends CacheModel implements MappingModelInterface, MappingModelCloneableInterface
{
    public $visible = ['id', 'mapping_version_id', 'floor_id', 'teeming', 'faction', 'polyline'];
    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'polyline_id',
        'teeming',
        'faction',
    ];
    public $with = ['polyline'];
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Get the dungeon route that this brushline is attached to.
     *
     * @return HasOne
     */
    public function polyline(): HasOne
    {
        return $this->hasOne(Polyline::class, 'model_id')->where('model_class', get_class($this));
    }

    /**
     * @return int|null
     */
    public function getDungeonId(): ?int
    {
        return optional($this->floor)->dungeon_id ?? null;
    }

    /**
     * @param MappingVersion $mappingVersion
     * @param MappingModelInterface|null $newParent
     * @return Model
     */
    public function cloneForNewMappingVersion(MappingVersion $mappingVersion, ?MappingModelInterface $newParent = null): Model
    {
        /** @var Model|MappingModelInterface $clone */
        $clone                     = clone $this;
        $clone->exists             = false;
        $clone->id                 = null;
        $clone->mapping_version_id = $mappingVersion->id;
        $clone->save();

        $this->polyline->cloneForNewMappingVersion($mappingVersion, $clone);

        return $clone;
    }


    public static function boot()
    {
        parent::boot();

        // Delete patrol properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item EnemyPatrol */
            if ($item->polyline !== null) {
                $item->polyline->delete();
            }
        });
    }
}
