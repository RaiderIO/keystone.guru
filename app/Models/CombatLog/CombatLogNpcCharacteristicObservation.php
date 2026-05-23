<?php

namespace App\Models\CombatLog;

use App\Models\Characteristic;
use App\Models\Npc\Npc;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $npc_id
 * @property int    $characteristic_id
 * @property Carbon $observed_on
 * @property string $combat_log_path
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Npc            $npc
 * @property Characteristic $characteristic
 *
 * @mixin Eloquent
 */
class CombatLogNpcCharacteristicObservation extends Model
{
    protected $connection = 'combatlog';

    protected $fillable = [
        'npc_id',
        'characteristic_id',
        'observed_on',
        'combat_log_path',
    ];

    public function casts(): array
    {
        return [
            'observed_on' => 'date',
        ];
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(Characteristic::class);
    }
}
