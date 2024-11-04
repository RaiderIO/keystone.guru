<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Interfaces\EventModelInterface;
use App\Models\Traits\HasLinkedAwakenedObelisk;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Eloquent;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int          $id
 * @property int          $dungeon_route_id
 * @property int          $floor_id
 * @property int          $polyline_id
 *
 * @property Carbon       $updated_at
 * @property Carbon       $created_at
 *
 * @property DungeonRoute $dungeonRoute
 * @property Polyline     $polyline
 * @property Floor        $floor
 *
 * @mixin Eloquent
 */
class Path extends Model implements EventModelInterface
{
    use HasLinkedAwakenedObelisk;

    protected $visible = ['id', 'floor_id', 'linked_awakened_obelisk_id', 'polyline'];

    protected $fillable = ['dungeon_route_id', 'floor_id', 'polyline_id', 'created_at', 'updated_at'];

    protected $casts = [
        'id'               => 'int',
        'dungeon_route_id' => 'int',
        'floor_id'         => 'int',
        'polyline_id'      => 'int',
    ];

    protected $with = ['polyline', 'linkedawakenedobelisks'];

    protected $appends = ['linked_awakened_obelisk_id'];

    public $timestamps = true;

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

    /**
     * @throws BindingResolutionException
     */
    public function getEventData(): array
    {
        /** @var CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = app()->make(CoordinatesServiceInterface::class);

        return array_merge([

        ], $this->polyline->getCoordinatesData($coordinatesService, $this->dungeonRoute->mappingVersion, $this->floor));
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
