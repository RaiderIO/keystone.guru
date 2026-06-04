<?php

namespace App\Models\CombatLog;

use App\Models\Floor\Floor;
use App\Models\Npc\Npc;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $challenge_mode_run_id
 * @property int    $floor_id
 * @property int    $npc_id
 * @property string $guid
 * @property float  $lat
 * @property float  $lng
 *
 * @property Carbon $created_at
 * @property Floor  $floor
 * @property Npc    $npc
 *
 * @author Wouter
 *
 * @since 01/06/2023
 *
 * @mixin Eloquent
 */
class EnemyPosition extends Model
{
    protected $fillable = [
        'challenge_mode_run_id',
        'floor_id',
        'npc_id',
        'guid',
        'lat',
        'lng',
        'created_at',
    ];

    protected $connection = 'combatlog';

    public $timestamps = false;

    public function challengeModeRun(): HasOne
    {
        return $this->hasOne(ChallengeModeRun::class);
    }

    public function floor(): HasOne
    {
        return $this->hasOne(HasOne::class);
    }

    public function npc(): HasOne
    {
        return $this->hasOne(Npc::class);
    }
}
