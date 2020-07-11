<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:42
 */

namespace App\Http\Controllers\Traits;

use App\Models\EnemyPack;
use Illuminate\Database\Eloquent\Builder;

trait ListsEnemyPacks
{

    /**
     * Lists all enemy packs on a floor. If enemies = true, $teeming will return more points based on teeming enemies.
     *
     * @param $floorId
     * @param bool $teeming
     * @param bool $enemies
     * @return EnemyPack[]|Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    function listEnemyPacks($floorId, $enemies = true, $teeming = false)
    {
        /** @var Builder $result */
        $result = null;
        $fields = ['id', 'floor_id', 'label', 'teeming', 'faction'];
        if ($enemies) {
            $result = EnemyPack::with(['enemies' => function ($query) use ($teeming) {
                /** @var $query \Illuminate\Database\Query\Builder */
                // Only include teeming enemies when requested
                if (!$teeming) {
                    $query->where('teeming', null);
                }
                $query->select(['id', 'enemy_pack_id', 'lat', 'lng']); // must select enemy_pack_id, else it won't return results /sadface
            }]);
        } else {
            $fields[] = 'vertices_json';
            $result = EnemyPack::query();
        }

        return $result->where('floor_id', $floorId)->get($fields);
    }
}