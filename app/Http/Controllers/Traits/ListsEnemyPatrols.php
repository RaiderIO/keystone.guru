<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Models\EnemyPatrol;

trait ListsEnemyPatrols
{
    /**
     * Lists all patrols of a floor.
     *
     * @param $floorId
     * @return EnemyPatrol[]
     */
    function listEnemyPatrols($floorId)
    {
        return EnemyPatrol::with('polyline')->where('floor_id', $floorId)->get();
    }
}