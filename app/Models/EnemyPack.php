<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property string $label
 * @property \App\Models\Floor $floor
 * @property \Illuminate\Support\Collection $vertices
 */
class EnemyPack extends Model
{
    public $hidden = ['created_at', 'updated_at'];

    function floor(){
        return $this->belongsTo('App\Models\Floor');
    }

    function vertices(){
        return $this->hasMany('App\Models\EnemyPackVertex');
    }
}
