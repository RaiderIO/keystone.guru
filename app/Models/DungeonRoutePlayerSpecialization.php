<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $character_class_specialization_id int
 * @property $index int
 *
 * @mixin Eloquent
 */
class DungeonRoutePlayerSpecialization extends Model
{
    public $hidden = ['id'];
    protected $fillable = [
        'character_class_specialization_id',
        'dungeon_route_id'
    ];

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
    public function characterclassspecialization()
    {
        return $this->belongsTo('App\Models\CharacterClassSpecialization');
    }

    /**
     * @return BelongsToMany
     */
    public function specializations()
    {
        return $this->belongsToMany('App\Models\DungeonRoutePlayerSpecialization', 'dungeon_route_player_specializations');
    }
}
