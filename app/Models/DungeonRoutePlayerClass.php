<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $character_class_id int
 * @property $index int
 */
class DungeonRoutePlayerClass extends Model
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
    public function characterclass()
    {
        return $this->belongsTo('App\Models\CharacterClass');
    }
}
