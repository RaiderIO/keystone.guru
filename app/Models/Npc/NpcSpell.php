<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\Spell\Spell;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int   $id
 * @property int   $npc_id
 * @property int   $spell_id
 *
 * @property Npc   $npc
 * @property Spell $spell
 *
 * @mixin Eloquent
 */
class NpcSpell extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = ['id', 'npc_id', 'spell_id'];

    protected $hidden = ['id', 'npc_id'];

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
