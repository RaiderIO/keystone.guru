<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeonroute_id int
 * @property $class_id int
 * @property $index int
 */
class DungeonRoutePlayerClass extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dungeonroute(){
        return $this->belongsTo('App\Models\DungeonRoute');
    }
}
