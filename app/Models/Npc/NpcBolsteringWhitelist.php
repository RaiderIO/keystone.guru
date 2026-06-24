<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $npc_id
 * @property int $whitelist_npc_id
 * @property Npc $npc
 * @property Npc $whitelistnpc
 *
 * @mixin Eloquent
 */
class NpcBolsteringWhitelist extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'npc_id',
        'whitelist_npc_id',
    ];

    public $with = ['whitelistnpc'];

    /** @return BelongsTo<Npc, $this> */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /** @return BelongsTo<Npc, $this> */
    public function whitelistnpc(): BelongsTo
    {
        // Without to prevent infinite recursion
        return $this->belongsTo(Npc::class, 'whitelist_npc_id')->without('npcbolsteringwhitelists');
    }
}
