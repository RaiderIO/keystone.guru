<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $character_race_id int
 * @property $index int
 *
 * @mixin Eloquent
 */
class DungeonRoutePlayerRace extends Model
{

    public $hidden = ['id'];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute', 'dungeon_route_id');
    }

    /**
     * @return BelongsTo
     */
    public function characterrace()
    {
        return $this->belongsTo('App\Models\CharacterRace');
    }

    /**
     * @return BelongsToMany
     */
    public function races()
    {
        return $this->belongsToMany('App\Models\DungeonRoutePlayerRace', 'dungeon_route_player_races');
    }
}
