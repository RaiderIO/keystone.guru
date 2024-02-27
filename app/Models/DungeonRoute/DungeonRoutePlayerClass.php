<?php

namespace App\Models\DungeonRoute;

use App\Models\CharacterClass;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int            $id
 * @property int            $dungeon_route_id
 * @property int            $character_class_id
 * @property int            $index
 * @property DungeonRoute   $dungeonRoute
 * @property CharacterClass $characterClass
 *
 * @mixin Eloquent
 */
class DungeonRoutePlayerClass extends Model
{
    public $hidden = ['id'];

    protected $fillable = [
        'character_class_id',
        'dungeon_route_id',
    ];

    public $timestamps = false;

    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function characterClass(): BelongsTo
    {
        return $this->belongsTo(CharacterClass::class);
    }
}
