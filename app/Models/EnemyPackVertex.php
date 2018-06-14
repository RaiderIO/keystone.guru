<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $enemy_pack_id
 * @property int $x
 * @property int $y
 */
class EnemyPackVertex extends Model
{
    /**
     * @var string Custom name because 'vertexs' is not a word
     */
    protected $table = 'enemy_pack_vertices';

    /**
     * @var bool Irrelevant to keep timestamps for each individual vertex when the time is already kept track of by the EnemyPack
     * to which these vertices belong.
     */
    public $timestamps = false;

    function enemypack(){
        return $this->belongsTo('App\Models\EnemyPack');
    }
}
