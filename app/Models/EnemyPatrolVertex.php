<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $enemy_pack_id
 * @property int $x
 * @property int $y
 */
class EnemyPatrolVertex extends Model
{
    /**
     * @var string Custom name because 'vertexs' is not a word
     */
    protected $table = 'enemy_partrol_vertices';

    /**
     * @var array Hide some columns which we don't need to echo to the user
     */
    public $hidden = ['enemy_id'];

    /**
     * @var bool Irrelevant to keep timestamps for each individual vertex.
     */
    public $timestamps = false;

    function enemy(){
        return $this->belongsTo('App\Models\Enemy');
    }
}
