<?php

namespace App\Models\Speedrun;

use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\Npc;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $npc_id
 * @property int $npc2_id
 * @property int $npc3_id
 * @property int $npc4_id
 * @property int $npc5_id
 * @property int $count
 *
 * @property Dungeon $dungeon
 * @property Npc $npc
 *
 * @mixin Eloquent
 */
class DungeonSpeedrunRequiredNpc extends CacheModel
{
    protected $visible = [
        'id',
        'npc_id',
        'npc2_id',
        'npc3_id',
        'npc4_id',
        'npc5_id',
        'count',
    ];

    protected $fillable = [
        'dungeon_id',
        'npc_id',
        'npc2_id',
        'npc3_id',
        'npc4_id',
        'npc5_id',
        'count',
    ];
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /**
     * @return BelongsTo
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }
}
