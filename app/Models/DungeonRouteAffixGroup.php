<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $affix_group_id int
 */
class DungeonRouteAffixGroup extends Model
{
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dungeonroute()
    {
        return $this->belongsTo('App\DungeonRoute');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function affixgroup()
    {
        return $this->belongsTo('App\AffixGroup');
    }
}
