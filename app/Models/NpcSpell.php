<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $npc_id
 * @property int $spell_id
 *
 * @mixin Eloquent
 */
class NpcSpell extends CacheModel
{
    public $timestamps = false;

    protected $fillable = ['id', 'npc_id', 'whitelist_npc_id'];

    /**
     * @return BelongsTo
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * @return BelongsTo
     */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
