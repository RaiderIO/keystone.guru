<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int The ID of this DungeonRoute.
 * @property $author_id int
 * @property $dungeon_id int
 * @property $faction_id int
 * @property $title string
 */
class DungeonRoute extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dungeon()
    {
        return $this->belongsTo('App\Models\Dungeon');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function faction()
    {
        return $this->belongsTo('App\Models\Faction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function races()
    {
        return $this->hasMany('App\Models\DungeonRoutePlayerRace');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function characterclasses(){
        return $this->belongsToMany('App\Models\CharacterClass', 'dungeon_route_player_classes');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function affixgroups()
    {
        return $this->hasMany('App\Models\DungeonRouteAffixGroup');
    }
}
