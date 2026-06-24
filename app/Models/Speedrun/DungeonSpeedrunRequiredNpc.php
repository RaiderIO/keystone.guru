<?php

namespace App\Models\Speedrun;

use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                                                    $id
 * @property int                                                    $floor_id
 * @property int                                                    $difficulty
 * @property int                                                    $count
 * @property Dungeon                                                $dungeon
 * @property Floor                                                  $floor
 * @property EloquentCollection<int, DungeonSpeedrunRequiredNpcNpc> $dungeonSpeedrunRequiredNpcNpcs
 *
 * @mixin Eloquent
 */
class DungeonSpeedrunRequiredNpc extends CacheModel
{
    use SeederModel;

    protected $visible = [
        'id',
        'floor_id',
        'difficulty',
        'count',
        'dungeonSpeedrunRequiredNpcNpcs',
    ];

    protected $fillable = [
        'floor_id',
        'difficulty',
        'count',
    ];

    protected $with = [
        'dungeonSpeedrunRequiredNpcNpcs',
    ];

    public $timestamps = false;

    /** @return BelongsTo<Floor, $this> */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /** @return BelongsTo<Dungeon, Floor> */
    public function dungeon(): BelongsTo
    {
        return $this->floor->dungeon();
    }

    /** @return HasMany<DungeonSpeedrunRequiredNpcNpc, $this> */
    public function dungeonSpeedrunRequiredNpcNpcs(): HasMany
    {
        return $this->hasMany(DungeonSpeedrunRequiredNpcNpc::class);
    }

    public function getDisplayText(): string
    {
        $parts = [];

        foreach ($this->dungeonSpeedrunRequiredNpcNpcs as $entry) {
            $parts[] = sprintf('%s (%d)', __($entry->npc->name), $entry->npc->id);
        }

        return implode(', ', $parts);
    }
}
