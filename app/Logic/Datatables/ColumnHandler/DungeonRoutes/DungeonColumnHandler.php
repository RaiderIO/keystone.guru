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

class DungeonColumnHandler extends DatatablesColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'dungeon_id');
    }

    protected function applyFilter(Builder $subBuilder, Builder $orderBuilder,  $columnData, $order, $generalSearch): void
    {
        // If we should search for this value
        if ($columnData['searchable'] === 'true') {
            $searchValue = $columnData['search']['value'];
            // -1 = all dungeons = no filter
            if ((int)$searchValue !== -1 && !empty($searchValue)) {
                $explode = explode('-', (string)$searchValue);
                if (count($explode) === 2) {
                    if ($explode[0] === 'season') {
                        // Joins need to be added to the main builder
                        $subBuilder->where('dungeon_routes.season_id', (int)$explode[1]);
                    } else if ($explode[0] === 'expansion') {
                        $subBuilder->where('dungeons.expansion_id', (int)$explode[1]);
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
                $orderBuilder->orderby('dungeons.expansion_id', $order['dir'] === 'asc' ? 'asc' : 'desc')
                    // Order either asc or desc, nothing else
                    ->orderBy($this->getColumnData(), $order['dir'] === 'asc' ? 'asc' : 'desc');
            }
        }
    }
}
