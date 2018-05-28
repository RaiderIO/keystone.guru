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
    public function expansion(){
        return $this->belongsTo('App\Models\Expansion');
    }
}
