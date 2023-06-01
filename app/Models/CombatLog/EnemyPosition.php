<?php

namespace App\Models\CombatLog;

use App\Models\Floor;
use App\Models\Npc;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @var int $id
 * @var string $guid
 * @var int $floor_id
 * @var int $npc_id
 * @var float $lat
 * @var float $lng
 *
 * @property Carbon $created_at
 *
 * @property Floor $floor
 * @property Npc $npc
 *
 * @package App\Models\CombatLog
 * @author Wouter
 * @since 01/06/2023
 *
 * @mixin Eloquent
 */
class EnemyPosition extends Model
{
    protected $fillable = [
        'guid',
        'floor_id',
        'npc_id',
        'lat',
        'lng',
        'created_at',
    ];

    protected $connection = 'combatlog';

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return HasOne
     */
    public function npc(): HasOne
    {
        return $this->hasOne(Npc::class);
    }
}
