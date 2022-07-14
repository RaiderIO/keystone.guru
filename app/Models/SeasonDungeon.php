<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int
 * @property $season_id int
 * @property $dungeon_id int
 * @property Season $season
 * @property Dungeon $dungeon
 *
 * @mixin Eloquent
 */
class SeasonDungeon extends CacheModel
{
    protected $fillable = ['season_id', 'dungeon_id'];
    public $with = ['season', 'dungeon'];
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * @return BelongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }
}
