<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\belongsTo;

/**
 * @property int $id
 * @property int $npc_id
 * @property int $whitelist_npc_id
 *
 * @property Npc $npc
 * @property Npc $whitelistnpc
 *
 * @mixin Eloquent
 */
class NpcBolsteringWhitelist extends CacheModel
{
    public $timestamps = false;

    protected $fillable = ['id', 'npc_id', 'whitelist_npc_id'];

    public $with = ['whitelistnpc'];

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
    public function whitelistnpc(): BelongsTo
    {
        // Without to prevent infinite recursion
        return $this->belongsTo(Npc::class, 'whitelist_npc_id')->without('npcbolsteringwhitelists');
    }
}
