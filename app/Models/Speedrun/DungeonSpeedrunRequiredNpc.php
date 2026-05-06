<?php

namespace App\Models\Speedrun;

use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Npc\Npc;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                      $id
 * @property int                      $floor_id
 * @property int                      $npc_id
 * @property int                      $npc2_id
 * @property int                      $npc3_id
 * @property int                      $npc4_id
 * @property int                      $npc5_id
 * @property int                      $difficulty
 * @property int                      $count
 * @property Dungeon                  $dungeon
 * @property Floor                    $floor
 * @property \App\Models\Npc\Npc      $npc
 * @property Npc|null                 $npc2
 * @property Npc|null                 $npc3
 * @property Npc|null                 $npc4
 * @property \App\Models\Npc\Npc|null $npc5
 *
 * @mixin Eloquent
 */
class DungeonSpeedrunRequiredNpc extends CacheModel
{
    use SeederModel;

    protected $visible = [
        'id',
        'floor_id',
        'npc_id',
        'npc2_id',
        'npc3_id',
        'npc4_id',
        'npc5_id',
        'difficulty',
        'count',
    ];

    protected $fillable = [
        'floor_id',
        'npc_id',
        'npc2_id',
        'npc3_id',
        'npc4_id',
        'npc5_id',
        'difficulty',
        'count',
    ];

    protected $with = [
        'npc',
        'npc2',
        'npc3',
        'npc4',
        'npc5',
    ];

    public $timestamps = false;

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function dungeon(): BelongsTo
    {
        return $this->floor->dungeon();
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function npc2(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function npc3(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function npc4(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function npc5(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

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
                $parts[] = sprintf('%s (%d)', __($npc->name), $npc->id);
            }
        }

        return implode(', ', $parts);
    }
}
