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
use Illuminate\Support\Collection;

trait ListsEnemyPacks
{
    /**
     * Lists all enemy packs on a floor. If enemies = true, $teeming will return more points based on teeming enemies.
     *
     * @return Collection<EnemyPack>
     */
    public function listEnemyPacks(int $floorId, bool $enemies = true, bool $teeming = false): Collection
    {
        /** @var Builder $result */
        $fields = [
            'id',
            'floor_id',
            'label',
            'teeming',
            'faction',
        ];
        if ($enemies) {
            $result = EnemyPack::with([
                'enemies' => static function ($query) use ($teeming) {
                    /** @var $query \Illuminate\Database\Query\Builder */
                    // Only include teeming enemies when requested
                    if (!$teeming) {
                        $query->whereNull('teeming');
                    }

                    $query->select([
                        'id',
                        'enemy_pack_id',
                        'lat',
                        'lng',
                    ]);
                    // must select enemy_pack_id, else it won't return results /sadface
                },
            ]);
        } else {
            $fields[] = 'vertices_json';
            $result   = EnemyPack::query();
        }

        return $result->where('floor_id', $floorId)->get($fields);
    }
}
