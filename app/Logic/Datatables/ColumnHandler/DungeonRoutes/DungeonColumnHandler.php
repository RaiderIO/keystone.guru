<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables\ColumnHandler\DungeonRoutes;

use App\Logic\Datatables\ColumnHandler\DatatablesColumnHandler;
use App\Logic\Datatables\DatatablesHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DungeonColumnHandler extends DatatablesColumnHandler
{

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'dungeon_id');
    }

    protected function applyFilter(Builder $builder, $columnData, $order, $generalSearch)
    {
        // If we should search for this value
        if ($columnData['searchable'] === 'true') {
            $searchValue = $columnData['search']['value'];
            // -1 = all dungeons = no filter
            if ((int)$searchValue !== -1 && !empty($searchValue)) {
                $explode = explode('-', $searchValue);
                if (count($explode) === 2) {
                    $seasonId = $explode[1];
                    $builder->join('season_dungeons', 'season_dungeons.season_id', '=', DB::raw($seasonId))
                        ->whereColumn('dungeon_routes.dungeon_id', 'season_dungeons.dungeon_id');
                } else {
                    $builder->where('dungeon_routes.dungeon_id', $searchValue);
                }
            }
        }

        // If we should order
        if ($columnData['orderable'] === 'true') {
            // Order on this column?
            if (!is_null($order)) {
                // Always order based on expansion - the most recent expansion should always come on top
                $builder->orderby('dungeons.expansion_id', 'DESC')
                    // Order either asc or desc, nothing else
                    ->orderBy($this->getColumnData(), $order['dir'] === 'asc' ? 'asc' : 'desc');
            }
        }
    }
}
