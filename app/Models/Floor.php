<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $index
 * @property string $name
 */
class Floor extends Model
{
    public function dungeon(){
        return $this->belongsTo('App\Models\Dungeon');
    }

    public function connectedFloors(){
        return $this->belongsToMany('App\Models\Floor')->withPivot(['floor1_id'])->withTimestamps();
    }
}
