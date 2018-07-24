<?php

namespace App\Models;

/**
 * @property int $id
 * @property int $icon_file_id
 * @property string $name
 * @property string $color
 */
class Expansion extends IconFileModel
{
    public function dungeons(){
        return $this->hasMany('App\Models\Dungeon');
    }
}
