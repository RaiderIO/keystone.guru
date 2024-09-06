<?php

namespace App\Models\CombatLog;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use Carbon\CarbonInterval;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int                       $id
 * @property int                       $dungeon_id
 * @property int                       $dungeon_route_id
 * @property int                       $level
 * @property bool                      $success
 * @property int                       $total_time_ms
 * @property bool                      $duplicate
 * @property Carbon                    $created_at
 * @property Dungeon                   $dungeon
 * @property DungeonRoute              $dungeonRoute
 * @property ChallengeModeRunData      $challengeModeRunData
 * @property Collection<EnemyPosition> $enemyPositions
 *
 * @author Wouter
 *
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
        'duplicate',
        'created_at',
    ];

    protected $with = [
        'challengeModeRunData',
    ];

    public function dungeon(): HasOne
    {
        return $this->hasOne(Dungeon::class);
    }

    public function dungeonRoute(): HasOne
    {
        return $this->hasOne(DungeonRoute::class);
    }

    public function enemyPositions(): HasMany
    {
        return $this->hasMany(EnemyPosition::class);
    }

    public function challengeModeRunData(): HasOne
    {
        return $this->hasOne(ChallengeModeRunData::class);
    }

    /**
     * @throws Exception
     */
    public function getFormattedElapsedTime(): string
    {
        $milliseconds = $this->total_time_ms;
        $hours        = floor($milliseconds / 3600000);
        $milliseconds -= ($hours * 3600000);

        $minutes      = floor($milliseconds / 60000);
        $milliseconds -= ($minutes * 60000);

        $seconds      = floor($milliseconds / 1000);
        $milliseconds -= ($seconds * 1000);

        $interval = CarbonInterval::create(
            0,
            0,
            0,
            0,
            $hours,
            $minutes,
            $seconds,
            $milliseconds
        );

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d.%02d', $interval->hours, $interval->minutes, $interval->seconds, $interval->microseconds);
        } else {
            return sprintf('%02d:%02d.%02d', $interval->minutes, $interval->seconds, $interval->microseconds);
        }
    }
}
