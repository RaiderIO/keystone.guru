<?php

namespace App\Models\Speedrun;

use App\Models\CacheModel;
use App\Models\Npc\Npc;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dungeon_speedrun_required_npc_id
 * @property int $npc_id
 *
 * @property DungeonSpeedrunRequiredNpc $dungeonSpeedrunRequiredNpc
 * @property Npc                        $npc
 *
 * @mixin Eloquent
 */
class DungeonSpeedrunRequiredNpcNpc extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'dungeon_speedrun_required_npc_id',
        'npc_id',
    ];

    protected $hidden = [
        'id',
        'dungeon_speedrun_required_npc_id',
    ];

    protected $visible = [
        'npc_id',
    ];

    protected $with = [
        'npc',
    ];

    /** @return BelongsTo<DungeonSpeedrunRequiredNpc, $this> */
    public function dungeonSpeedrunRequiredNpc(): BelongsTo
    {
        return $this->belongsTo(DungeonSpeedrunRequiredNpc::class);
    }

    /** @return BelongsTo<Npc, $this> */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }
}
