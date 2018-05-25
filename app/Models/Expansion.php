<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $color
 */
class Expansion extends Model
{
    public function icon(){
        return $this->hasOne('App\Models\File', 'model_id');
    }
}
