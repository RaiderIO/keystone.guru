<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $route_id
 * @property int $lat
 * @property int $lng
 */
class PathVertex extends Model
{
    /**
     * @var string Custom name because 'vertexs' is not a word
     */
    protected $table = 'path_vertices';

    /**
     * @var array Hide some columns which we don't need to echo to the user
     */
    public $hidden = ['id', 'path_id'];

    public $fillable = ['path_id', 'lat', 'lng'];

    /**
     * @var bool Irrelevant to keep timestamps for each individual vertex.
     */
    public $timestamps = false;

    function route(){
        return $this->belongsTo('App\Models\Path');
    }
}
