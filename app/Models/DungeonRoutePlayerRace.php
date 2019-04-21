<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $character_race_id int
 * @property $index int
 *
 * @mixin \Eloquent
 */
class DungeonRoutePlayerRace extends Model
{

    public $hidden = ['id'];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function characterrace()
    {
        return $this->belongsTo('App\Models\CharacterRace');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function races()
    {
        return $this->belongsToMany('App\Models\DungeonRoutePlayerRace', 'dungeon_route_player_races');
    }
}
