<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Models\EnemyPatrol;
use Illuminate\Support\Collection;

trait ListsEnemyPatrols
{
    /**
     * Lists all patrols of a floor.
     *
     * @param int $floorId
     * @return Collection
     */
    function listEnemyPatrols(int $floorId): Collection
    {
        return EnemyPatrol::with('polyline')->where('floor_id', $floorId)->get();
    }
}