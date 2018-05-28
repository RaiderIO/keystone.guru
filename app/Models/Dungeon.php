<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dungeon extends Model
{
    public function expansion(){
        return $this->belongsTo('App\Models\Expansion');
    }
}
