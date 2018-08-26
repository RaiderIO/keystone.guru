<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $enemy_patrol_id
 * @property int $lat
 * @property int $lng
 */
class EnemyPatrolVertex extends Model
{
    /**
     * @var string Custom name because 'vertexs' is not a word
     */
    protected $table = 'enemy_patrol_vertices';

    /**
     * @var array Hide some columns which we don't need to echo to the user
     */
    public $hidden = ['enemy_patrol_id'];

    /**
     * @var bool Irrelevant to keep timestamps for each individual vertex.
     */
    public $timestamps = false;

    function enemypatrol(){
        return $this->belongsTo('App\Models\EnemyPatrol');
    }
}
