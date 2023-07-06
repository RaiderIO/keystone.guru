<?php

namespace App\Models;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $character_race_id
 * @property int $index
 *
 * @property DungeonRoute $dungeonroute
 * @property CharacterRace $characterrace
 * @property Collection|DungeonRoutePlayerRace[] $races
 *
 * @mixin Eloquent
 */
class DungeonRoutePlayerRace extends Model
{
    public $hidden = ['id'];
    protected $fillable = [
        'dungeon_route_id',
        'character_race_id',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class, 'dungeon_route_id');
    }

    /**
     * @return BelongsTo
     */
    public function characterrace(): BelongsTo
    {
        return $this->belongsTo(CharacterRace::class);
    }

    /**
     * @return BelongsToMany
     */
    public function races(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoutePlayerRace::class, 'dungeon_route_player_races');
    }
}
