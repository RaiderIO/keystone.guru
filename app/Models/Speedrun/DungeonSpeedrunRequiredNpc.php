<?php

namespace App\Models\Speedrun;

use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\Floor;
use App\Models\Npc;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $floor_id
 * @property int $npc_id
 * @property int $npc2_id
 * @property int $npc3_id
 * @property int $npc4_id
 * @property int $npc5_id
 * @property int $mode
 * @property int $count
 *
 * @property Dungeon $dungeon
 * @property Floor $floor
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

    const MODE_10_MAN = 1;
    const MODE_25_MAN = 2;

    const MODE_ALL = [
        self::MODE_10_MAN,
        self::MODE_25_MAN,
    ];

    protected $visible = [
        'id',
        'floor_id',
        'npc_id',
        'npc2_id',
        'npc3_id',
        'npc4_id',
        'npc5_id',
        'mode',
        'count',
    ];

    protected $fillable = [
        'floor_id',
        'npc_id',
        'npc2_id',
        'npc3_id',
        'npc4_id',
        'npc5_id',
        'mode',
        'count',
    ];
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return BelongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->floor->dungeon();
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
