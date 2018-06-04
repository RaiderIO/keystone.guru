<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int The ID of this Dungeon.
 * @property $expansion_id int The linked expansion to this dungeon.
 * @property $name string The name of the dungeon
 */
class Dungeon extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expansion(){
        return $this->belongsTo('App\Models\Expansion');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function floors(){
        return $this->hasMany('App\Models\Floor');
    }
}
