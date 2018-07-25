<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $character_race_id int
 * @property $index int
 */
class DungeonRoutePlayerRace extends Model
{

    public $hidden = ['id', 'dungeon_route_id'];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dungeonroute(){
        return $this->belongsTo('App\Models\DungeonRoute');
    }
}
