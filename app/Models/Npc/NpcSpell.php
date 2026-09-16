<?php

namespace App\Models\Npc;

use App\Models\Spell\Spell;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $npc_id
 * @property int $spell_id
 *
 * @property Npc   $npc
 * @property Spell $spell
 *
 * @mixin Eloquent
 */
class NpcSpell extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id',
        'npc_id',
        'spell_id',
    ];

    protected $hidden = [
        'id',
        'npc_id',
    ];

    /** @return BelongsTo<Npc, $this> */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /** @return BelongsTo<Spell, $this> */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
