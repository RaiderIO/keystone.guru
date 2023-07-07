<?php

namespace App\Models\CombatLog;

use App\Models\Dungeon;
use App\Models\DungeonRoute;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property int                        $id
 * @property int                        $dungeon_id
 * @property int                        $dungeon_route_id
 * @property int                        $level
 * @property bool                       $success
 * @property int                        $total_time_ms
 *
 * @property Carbon                     $created_at
 *
 * @property Dungeon                    $dungeon
 * @property DungeonRoute               $dungeonRoute
 * @property ChallengeModeRunData       $challengeModeRunData
 * @property Collection|EnemyPosition[] $enemyPositions
 *
 * @package App\Models\CombatLog
 * @author Wouter
 * @since 02/06/2023
 *
 * @mixin Eloquent
 */
class ChallengeModeRun extends Model
{
    protected $connection = 'combatlog';

    public $timestamps = false;

    protected $fillable = [
        'dungeon_id',
        'dungeon_route_id',
        'level',
        'success',
        'total_time_ms',
        'created_at',
    ];

    /**
     * @return HasOne
     */
    public function dungeon(): HasOne
    {
        return $this->hasOne(Dungeon::class);
    }

    /**
     * @return HasOne
     */
    public function dungeonRoute(): HasOne
    {
        return $this->hasOne(DungeonRoute::class);
    }

    /**
     * @return HasMany
     */
    public function enemyPositions(): HasMany
    {
        return $this->hasMany(EnemyPosition::class);
    }

    /**
     * @return HasOne
     */
    public function challengeModeRunData(): HasOne
    {
        return $this->hasOne(ChallengeModeRunData::class);
    }
}
