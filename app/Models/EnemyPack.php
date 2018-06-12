<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnemyPack extends Model
{
    function floor(){
        return $this->belongsTo('App\Models\Floor');
    }

    function vertices(){
        return $this->hasMany('App\Models\EnemyPackVertex');
    }
}
