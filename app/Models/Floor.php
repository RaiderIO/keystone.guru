<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $index
 * @property boolean $default
 * @property string $name
 *
 * @property Dungeon $dungeon
 *
 * @property Collection $enemies
 * @property Collection $enemypacks
 * @property Collection $enemypatrols
 * @property Collection $mapicons
 * @property Collection $connectedFloors
 * @property Collection $directConnectedFloors
 * @property Collection $reverseConnectedFloors
 *
 * @mixin Eloquent
 */
class Floor extends Model
{
    public $hidden = ['dungeon_id', 'created_at', 'updated_at'];

    /**
     * @return BelongsTo
     */
    public function dungeon()
    {
        return $this->belongsTo('App\Models\Dungeon');
    }

    /**
     * @return HasMany
     */
    function enemies()
    {
        return $this->hasMany('App\Models\Enemy');
    }

    /**
     * @return HasMany
     */
    function enemypacks()
    {
        return $this->hasMany('App\Models\EnemyPack');
    }

    /**
     * @return HasMany
     */
    function enemypatrols()
    {
        return $this->hasMany('App\Models\EnemyPatrol');
    }

    /**
     * @return HasMany
     */
    function mapicons()
    {
        return $this->hasMany('App\Models\MapIcon')->where('dungeon_route_id', -1);
    }

    /**
     * @return HasMany
     */
    function floorcouplings()
    {
        return $this->hasMany('App\Models\FloorCoupling', 'floor1_id');
    }

    /**
     * @return Collection A list of all connected floors, regardless of direction
     */
    public function connectedFloors()
    {
        return $this->directConnectedFloors->merge($this->reverseConnectedFloors);
    }

    /**
     * @return BelongsToMany
     */
    public function directConnectedFloors()
    {
        return $this->belongsToMany('App\Models\Floor', 'floor_couplings', 'floor1_id', 'floor2_id');
    }

    /**
     * @return BelongsToMany
     */
    public function reverseConnectedFloors()
    {
        return $this->belongsToMany('App\Models\Floor', 'floor_couplings', 'floor2_id', 'floor1_id');
    }
}
