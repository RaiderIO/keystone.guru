<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $character_class_specialization_id int
 * @property $index int
 */
class DungeonRoutePlayerSpecialization extends Model
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
    public function characterclassspecialization()
    {
        return $this->belongsTo('App\Models\CharacterClassSpecialization');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function specializations()
    {
        return $this->belongsToMany('App\Models\DungeonRoutePlayerSpecialization', 'dungeon_route_player_specializations');
    }
}
