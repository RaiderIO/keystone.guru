<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Traits\HasLinkedAwakenedObelisk;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $floor_id
 * @property int $polyline_id
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property DungeonRoute $dungeonRoute
 * @property Polyline     $polyline
 * @property Floor        $floor
 *
 * @mixin Eloquent
 */
class Path extends Model
{
    use HasLinkedAwakenedObelisk;

    protected $visible = [
        'id',
        'floor_id',
        'linked_awakened_obelisk_id',
        'polyline',
    ];

    protected $fillable = [
        'dungeon_route_id',
        'floor_id',
        'polyline_id',
        'created_at',
        'updated_at',
    ];

    protected $with = [
        'polyline',
        'linkedawakenedobelisks',
    ];

    protected $appends = ['linked_awakened_obelisk_id'];

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'id'               => 'int',
            'dungeon_route_id' => 'int',
            'floor_id'         => 'int',
            'polyline_id'      => 'int',
        ];
    }

    /**
     * Get the dungeon route that this route is attached to.
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function polyline(): HasOne
    {
        return $this->hasOne(Polyline::class, 'model_id')->where('model_class', static::class);
    }

    /**
     * Get the floor that this polyline is drawn on.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        // Delete Path properly if it gets deleted
        static::deleting(static function (Path $path) {
            $path->linkedawakenedobelisks()->delete();
            $path->polyline()->delete();
        });
    }
}
