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
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DungeonColumnHandler extends DatatablesColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'dungeon_id');
    }

    protected function applyFilter(Builder $subBuilder, $columnData, $order, $generalSearch)
    {
        // If we should search for this value
        if ($columnData['searchable'] === 'true') {
            $searchValue = $columnData['search']['value'];
            // -1 = all dungeons = no filter
            if ((int)$searchValue !== -1 && !empty($searchValue)) {
                $explode = explode('-', (string)$searchValue);
                if (count($explode) === 2) {
                    if ($explode[0] === 'season') {
                        $seasonId = $explode[1];
                        // Joins need to be added to the main builder
                        $this->getDtHandler()->getBuilder()
                            ->join('season_dungeons', 'season_dungeons.season_id', '=', DB::raw($seasonId));
                        $subBuilder->whereColumn('dungeon_routes.dungeon_id', '=', 'season_dungeons.dungeon_id');
                    } else if ($explode[0] === 'expansion') {
                        $subBuilder->where('dungeons.expansion_id', $explode[1]);
                    } else {
                        throw new Exception(sprintf('Unable to find prefix %s', $explode[0]));
                    }
                } else {
                    $subBuilder->where('dungeon_routes.dungeon_id', $searchValue);
                }
            }
        }

        // If we should order
        if ($columnData['orderable'] === 'true') {
            // Order on this column?
            if (!is_null($order)) {
                // Always order based on expansion - the most recent expansion should always come on top
                $subBuilder->orderby('dungeons.expansion_id', 'DESC')
                    // Order either asc or desc, nothing else
                    ->orderBy($this->getColumnData(), $order['dir'] === 'asc' ? 'asc' : 'desc');
            }
        }
    }
}
