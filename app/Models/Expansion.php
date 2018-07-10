<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $icon_file_id
 * @property string $name
 * @property string $color
 */
class Expansion extends Model
{
    public function dungeons(){
        return $this->hasMany('App\Models\Dungeon');
    }

    public function icon(){
        return $this->hasOne('App\Models\File', 'model_id')->where('model_class', '=', get_class($this));
    }
}
