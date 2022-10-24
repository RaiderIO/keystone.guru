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
 * @property Npc|null $npc2
 * @property Npc|null $npc3
 * @property Npc|null $npc4
 * @property Npc|null $npc5
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

    /**
     * @return BelongsTo
     */
    public function npc2(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * @return BelongsTo
     */
    public function npc3(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * @return BelongsTo
     */
    public function npc4(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * @return BelongsTo
     */
    public function npc5(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * @return string
     */
    public function getDisplayText(): string
    {
        $parts = [];
        $npcs  = [
            $this->npc,
            $this->npc2,
            $this->npc3,
            $this->npc4,
            $this->npc5,
        ];

        foreach ($npcs as $npc) {
            if ($npc !== null) {
                $parts[] = sprintf('%s (%d)', $npc->name, $npc->id);
            }
        }

        return implode(', ', $parts);
    }
}
