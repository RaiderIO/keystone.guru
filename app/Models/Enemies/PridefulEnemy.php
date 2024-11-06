<?php

namespace App\Models\Enemies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Traits\HasLatLng;
use App\Models\Traits\Reportable;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int          $id
 * @property int          $dungeon_route_id
 * @property int          $enemy_id
 * @property int          $floor_id
 * @property float        $lat
 * @property float        $lng
 *
 * @property DungeonRoute $dungeonRoute
 * @property Enemy        $enemy
 * @property Floor        $floor
 *
 * @mixin Eloquent
 */
class PridefulEnemy extends Model
{
    use HasLatLng;
    use Reportable;

    protected $fillable = ['dungeon_route_id', 'enemy_id', 'floor_id', 'lat', 'lng'];

    protected $visible = ['enemy_id', 'floor_id', 'lat', 'lng'];

    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
