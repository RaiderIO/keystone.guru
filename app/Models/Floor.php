<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $index
 * @property string $name
 * @property Dungeon $dungeon
 * @property \Illuminate\Support\Collection $enemypacks
 * @property \Illuminate\Support\Collection $connectedFloors
 * @property \Illuminate\Support\Collection $directConnectedFloors
 * @property \Illuminate\Support\Collection $reverseConnectedFloors
 */
class Floor extends Model
{
    public $hidden = ['dungeon_id', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dungeon()
    {
        return $this->belongsTo('App\Models\Dungeon');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function enemies()
    {
        return $this->hasMany('App\Models\Enemy');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function enemypacks()
    {
        return $this->hasMany('App\Models\EnemyPack');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function enemypatrols()
    {
        return $this->hasMany('App\Models\EnemyPatrol');
    }

    /**
     * @return \Illuminate\Support\Collection A list of all connected floors, regardless of direction
     */
    public function connectedFloors()
    {
        return $this->directConnectedFloors->merge($this->reverseConnectedFloors);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function directConnectedFloors()
    {
        return $this->belongsToMany('App\Models\Floor', 'floor_couplings', 'floor1_id', 'floor2_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function reverseConnectedFloors()
    {
        return $this->belongsToMany('App\Models\Floor', 'floor_couplings', 'floor2_id', 'floor1_id');
    }
}
