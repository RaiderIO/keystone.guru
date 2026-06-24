<?php

namespace App\Models\Speedrun;

use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int     $id
 * @property int     $dungeon_id
 * @property int     $difficulty
 * @property Dungeon $dungeon
 *
 * @mixin Eloquent
 */
class DungeonSpeedrunDifficulty extends CacheModel
{
    use SeederModel;

    protected $visible = [
        'id',
        'dungeon_id',
        'difficulty',
    ];

    protected $fillable = [
        'dungeon_id',
        'difficulty',
    ];

    public $timestamps = false;

    /** @return BelongsTo<Dungeon, $this> */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }
}
