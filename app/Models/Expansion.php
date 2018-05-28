<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $color
 */
class Expansion extends Model
{
    public function dungeons(){
        return $this->hasMany('App\Models\Dungeon');
    }

    public function icon(){
        return $this->hasOne('App\Models\File', 'model_id');
    }
}
