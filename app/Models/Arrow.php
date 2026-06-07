<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $floor_id
 * @property int $polyline_id
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property DungeonRoute  $dungeonRoute
 * @property Polyline|null $polyline
 * @property Floor         $floor
 *
 * @mixin Eloquent
 */
class Arrow extends Model
{
    protected $visible = [
        'id',
        'floor_id',
        'polyline',
    ];

    protected $fillable = [
        'dungeon_route_id',
        'floor_id',
        'polyline_id',
        'created_at',
        'updated_at',
    ];

    protected $with = ['polyline'];

    protected function casts(): array
    {
        return [
            'id'               => 'int',
            'dungeon_route_id' => 'int',
            'floor_id'         => 'int',
            'polyline_id'      => 'int',
        ];
    }

    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function polyline(): HasOne
    {
        return $this->hasOne(Polyline::class, 'model_id')->where('model_class', static::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(static function (Arrow $arrow) {
            $arrow->polyline()->delete();
        });
    }
}
