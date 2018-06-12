<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $index
 * @property string $name
 * @property \Illuminate\Support\Collection $directConnectedFloors
 * @property \Illuminate\Support\Collection $reverseConnectedFloors
 */
class Floor extends Model
{
    public function dungeon()
    {
        return $this->belongsTo('App\Models\Dungeon');
    }

    function enemypacks()
    {
        return $this->hasMany('App\Models\EnemyPack');
    }

    /**
     * @return \Illuminate\Support\Collection A list of all connected floors, regardless of direction
     */
    public function connectedFloors()
    {
        return $this->directConnectedFloors->merge($this->reverseConnectedFloors);
    }

    public function reverseConnectedFloors()
    {
        return $this->belongsToMany('App\Models\Floor', 'floor_couplings', 'floor2_id', 'floor1_id')->withTimestamps();
    }

    public function directConnectedFloors()
    {
        return $this->belongsToMany('App\Models\Floor', 'floor_couplings', 'floor1_id', 'floor2_id')->withTimestamps();
    }
}
