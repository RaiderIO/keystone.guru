<?php

namespace App\Models\CombatLog;

use App\Models\Dungeon;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $level
 * @property bool $success
 * @property int $total_time_ms
 *
 * @property Carbon $created_at
 *
 * @property Dungeon $dungeon
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
    protected $fillable = [
        'dungeon_id',
        'level',
        'success',
        'total_time_ms',
        'created_at',
    ];

    protected $connection = 'combatlog';

    public $timestamps = false;

    /**
     * @return HasOne
     */
    public function dungeon(): HasOne
    {
        return $this->hasOne(Dungeon::class);
    }

    /**
     * @return HasMany
     */
    public function enemyPositions(): HasMany
    {
        return $this->hasMany(EnemyPosition::class);
    }
}
