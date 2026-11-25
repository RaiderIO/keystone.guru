<?php

namespace App\Models\CombatLog;

use Database\Factories\CombatLog\CombatLogEncounterObservationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * What one boss encounter attempt in one combat log file showed about its units, raw. The shape of `data` is
 * built by {@see \App\Service\CombatLog\DataExtractors\EncounterMechanics\EncounterObservation::toData()}; all times
 * in it are milliseconds since ENCOUNTER_START.
 *
 * @property int                  $id
 * @property int                  $dungeon_id
 * @property int                  $encounter_id    The ENCOUNTER_START id, not the journal id in npcs.encounter_id
 * @property string               $run_key
 * @property string               $combat_log_path
 * @property int|null             $key_level
 * @property int|null             $affix_group_id
 * @property bool                 $success
 * @property int                  $duration_ms
 * @property array<string, mixed> $data
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Eloquent
 */
class CombatLogEncounterObservation extends Model
{
    /** @use HasFactory<CombatLogEncounterObservationFactory> */
    use HasFactory;

    protected $connection = 'combatlog';

    protected $fillable = [
        'dungeon_id',
        'encounter_id',
        'run_key',
        'combat_log_path',
        'key_level',
        'affix_group_id',
        'success',
        'duration_ms',
        'data',
    ];

    public function casts(): array
    {
        return [
            'dungeon_id'     => 'integer',
            'encounter_id'   => 'integer',
            'key_level'      => 'integer',
            'affix_group_id' => 'integer',
            'success'        => 'boolean',
            'duration_ms'    => 'integer',
            'data'           => 'array',
        ];
    }
}
