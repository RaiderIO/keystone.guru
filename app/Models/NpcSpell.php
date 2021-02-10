<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int
 * @property $npc_id int
 * @property $spell_id int
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
    public function npc()
    {
        return $this->belongsTo('App\Models\Npc');
    }

    /**
     * @return BelongsTo
     */
    public function spell()
    {
        return $this->belongsTo('App\Models\Spell');
    }
}
