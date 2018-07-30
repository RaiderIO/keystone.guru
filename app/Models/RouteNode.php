<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property \Illuminate\Support\Collection $connectedNodes
 * @property \Illuminate\Support\Collection $directConnectedNodes
 * @property \Illuminate\Support\Collection $directConnectedFloors
 */
class RouteNode extends Model
{
    //
    public $timestamps = false;

    /**
     * @return \Illuminate\Support\Collection A list of all connected floors, regardless of direction
     */
    public function connectedNodes()
    {
        return $this->directConnectedNodes->merge($this->reverseConnectedNodes);
    }

    public function directConnectedNodes()
    {
        return $this->belongsToMany('App\Models\RouteNode', 'route_node_connections', 'node1_id', 'node2_id');
    }

    public function reverseConnectedNodes()
    {
        return $this->belongsToMany('App\Models\RouteNode', 'route_node_connections', 'node2_id', 'node1_id');
    }
}
