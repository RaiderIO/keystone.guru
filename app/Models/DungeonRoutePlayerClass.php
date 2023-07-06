<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $character_class_id
 * @property int $index
 *
 * @mixin \Eloquent
 */
class DungeonRoutePlayerClass extends Model
{

    public $hidden = ['id'];
    protected $fillable = [
        'character_class_id',
        'dungeon_route_id',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return BelongsTo
     */
    public function characterclass(): BelongsTo
    {
        return $this->belongsTo(CharacterClass::class);
    }
}
