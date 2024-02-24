<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int     $id
 * @property int     $season_id
 * @property int     $dungeon_id
 * @property Season  $season
 * @property Dungeon $dungeon
 *
 * @mixin Eloquent
 */
class SeasonDungeon extends CacheModel
{
    use SeederModel;

    protected $fillable   = ['season_id', 'dungeon_id'];
    public    $with       = ['season', 'dungeon'];
    public    $timestamps = false;

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
