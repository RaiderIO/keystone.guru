<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int     $id
 * @property int     $npc_id
 * @property int     $dungeon_id
 *
 * @property Npc     $npc
 * @property Dungeon $dungeon
 *
 * @mixin Eloquent
 */
class NpcDungeon extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = ['id', 'npc_id', 'dungeon_id'];

    public $with = ['dungeon'];

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }
}
