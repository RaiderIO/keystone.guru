<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeonroute_id int
 * @property $race_id int
 * @property $index int
 */
class DungeonRoutePlayerRace extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dungeonroute(){
        return $this->belongsTo('App\Models\DungeonRoute');
    }
}
