<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $index
 * @property int|null $mdt_sub_level
 * @property string $name
 * @property boolean $default
 * @property int $min_enemy_size
 * @property int $max_enemy_size
 * @property int $ingame_min_x
 * @property int $ingame_min_y
 * @property int $ingame_max_x
 * @property int $ingame_max_y
 * @property int|null $percentage_display_zoom
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
class Floor extends CacheModel
{
    protected $fillable = ['ingame_min_x', 'ingame_min_y', 'ingame_max_x', 'ingame_max_y'];

    public $timestamps = false;

    public $hidden = ['dungeon_id', 'created_at', 'updated_at'];

    /**
     * @return BelongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo('App\Models\Dungeon');
    }

    /**
     * @return HasMany
     */
    function enemies(): HasMany
    {
        return $this->hasMany('App\Models\Enemy');
    }

    /**
     * @return HasMany
     */
    function enemypacks(): HasMany
    {
        return $this->hasMany('App\Models\EnemyPack');
    }

    /**
     * @return HasMany
     */
    function enemypatrols(): HasMany
    {
        return $this->hasMany('App\Models\EnemyPatrol');
    }

    /**
     * @return HasMany
     */
    function mapicons(): HasMany
    {
        return $this->hasMany('App\Models\MapIcon')->where('dungeon_route_id', -1);
    }

    /**
     * @return HasMany
     */
    function floorcouplings(): HasMany
    {
        return $this->hasMany('App\Models\FloorCoupling', 'floor1_id');
    }

    /**
     * @return Collection A list of all connected floors, regardless of direction
     */
    public function connectedFloors(): Collection
    {
        return $this->directConnectedFloors->merge($this->reverseConnectedFloors);
    }

    /**
     * @return BelongsToMany
     */
    public function directConnectedFloors(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Floor', 'floor_couplings', 'floor1_id', 'floor2_id');
    }

    /**
     * @return BelongsToMany
     */
    public function reverseConnectedFloors(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Floor', 'floor_couplings', 'floor2_id', 'floor1_id');
    }
}
