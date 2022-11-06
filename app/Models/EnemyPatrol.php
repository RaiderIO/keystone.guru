<?php

namespace App\Models;

use App\Models\Mapping\MappingModelInterface;
use Eloquent;
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
class EnemyPatrol extends CacheModel implements MappingModelInterface
{
    public $visible = ['id', 'mapping_version_id', 'floor_id', 'teeming', 'faction', 'polyline'];
    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'polyline_id',
        'teeming',
        'faction'
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
     * @return int
     */
    public function getDungeonId(): int
    {
        return $this->floor->dungeon_id;
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
