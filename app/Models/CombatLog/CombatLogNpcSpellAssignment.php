<?php

namespace App\Models\CombatLog;

use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $npc_id
 * @property int    $spell_id
 * @property string $combat_log_path
 * @property string $raw_event
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Npc    $npc
 * @property Spell  $spell
 *
 * @mixin Eloquent
 */
class CombatLogNpcSpellAssignment extends Model
{
    protected $connection = 'combatlog';

    public $timestamps = true;

    protected $fillable = [
        'npc_id',
        'spell_id',
        'combat_log_path',
        'raw_event',
        'created_at',
        'updated_at',
    ];

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
