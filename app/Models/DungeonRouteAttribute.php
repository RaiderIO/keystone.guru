<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $route_attribute_id
 *
 * @mixin \Eloquent
 */
class DungeonRouteAttribute extends Model
{
    public $timestamps = false;
}
