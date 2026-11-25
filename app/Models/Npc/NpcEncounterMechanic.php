<?php

namespace App\Models\Npc;

use App\Models\Dungeon;
use App\Models\Spell\Spell;
use Database\Factories\Npc\NpcEncounterMechanicFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A boss encounter mechanic that recurred across the observed kills of the encounter, built by
 * `combatlog:buildencountermechanics` from `combat_log_encounter_observations`. Derived from combat logs only, so a
 * deleted row comes back with the next build rather than from a seeder. All times are milliseconds since
 * ENCOUNTER_START.
 *
 * @property int                       $id
 * @property int                       $dungeon_id
 * @property int                       $encounter_id     The ENCOUNTER_START id, not the journal id in npcs.encounter_id
 * @property NpcEncounterMechanicType  $type
 * @property int|null                  $npc_id
 * @property int|null                  $spell_id
 * @property int|null                  $first_ms
 * @property float|null                $first_health_pct The boss health % the mechanic first happens at, when it is health-triggered
 * @property int|null                  $cooldown_ms
 * @property int|null                  $duration_ms
 * @property int|null                  $count
 * @property int                       $run_count        The kills that showed the mechanic
 * @property int                       $total_run_count  The kills considered
 * @property array<string, mixed>|null $data
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Dungeon    $dungeon
 * @property Npc|null   $npc
 * @property Spell|null $spell
 *
 * @mixin Eloquent
 */
class NpcEncounterMechanic extends Model
{
    /** @use HasFactory<NpcEncounterMechanicFactory> */
    use HasFactory;

    protected $fillable = [
        'dungeon_id',
        'encounter_id',
        'type',
        'npc_id',
        'spell_id',
        'first_ms',
        'first_health_pct',
        'cooldown_ms',
        'duration_ms',
        'count',
        'run_count',
        'total_run_count',
        'data',
    ];

    /**
     * @return BelongsTo<Dungeon, $this>
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /**
     * @return BelongsTo<Npc, $this>
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * @return BelongsTo<Spell, $this>
     */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }

    /**
     * @return list<int>
     */
    public function getSharedHealthNpcIds(): array
    {
        return array_values(array_map(intval(...), $this->data['npc_ids'] ?? []));
    }

    public function casts(): array
    {
        return [
            'dungeon_id'       => 'integer',
            'encounter_id'     => 'integer',
            'type'             => NpcEncounterMechanicType::class,
            'npc_id'           => 'integer',
            'spell_id'         => 'integer',
            'first_ms'         => 'integer',
            'first_health_pct' => 'float',
            'cooldown_ms'      => 'integer',
            'duration_ms'      => 'integer',
            'count'            => 'integer',
            'run_count'        => 'integer',
            'total_run_count'  => 'integer',
            'data'             => 'array',
        ];
    }

    /**
     * An encounter time as m:ss, e.g. 1:03.
     */
    public static function formatTime(int $ms): string
    {
        $seconds = (int)round($ms / 1000);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
