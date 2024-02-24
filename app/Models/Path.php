<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Traits\HasLinkedAwakenedObelisk;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int          $id
 * @property int          $dungeon_route_id
 * @property int          $floor_id
 * @property int          $polyline_id
 * @property string       $updated_at
 * @property string       $created_at
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

    public    $visible    = ['id', 'floor_id', 'linked_awakened_obelisk_id', 'polyline'];
    public    $fillable   = ['dungeon_route_id', 'floor_id', 'polyline_id', 'created_at', 'updated_at'];
    public    $with       = ['polyline', 'linkedawakenedobelisks'];
    protected $appends    = ['linked_awakened_obelisk_id'];
    public    $timestamps = true;

    /**
     * Get the dungeon route that this route is attached to.
     *
     * @return BelongsTo
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return HasOne
     */
    public function polyline(): HasOne
    {
        return $this->hasOne(Polyline::class, 'model_id')->where('model_class', static::class);
    }

    /**
     * Get the floor that this polyline is drawn on.
     *
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public static function boot()
    {
        parent::boot();

        // Delete Path properly if it gets deleted
        static::deleting(function (Path $path) {
            $path->linkedawakenedobelisks()->delete();
            $path->polyline()->delete();
        });
    }
}
