<?php

namespace App\Models\Enemies;

use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\Traits\Reportable;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $enemy_id
 * @property int $floor_id
 * @property double $lat
 * @property double $lng
 *
 * @property DungeonRoute $dungeonroute
 * @property Enemy $enemy
 * @property Floor $floor
 *
 * @mixin Eloquent
 */
class PridefulEnemy extends Model
{
    use Reportable;

    protected $fillable = ['dungeon_route_id', 'enemy_id', 'floor_id', 'lat', 'lng'];
    protected $visible = ['enemy_id', 'floor_id', 'lat', 'lng'];

    /**
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return BelongsTo
     */
    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
