<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnemyPackVertex extends Model
{
    /**
     * @var string Custom name because 'vertexs' is not a word
     */
    protected $table = 'enemy_pack_vertices';

    function enemypack(){
        return $this->belongsTo('App\Models\EnemyPack');
    }
}
