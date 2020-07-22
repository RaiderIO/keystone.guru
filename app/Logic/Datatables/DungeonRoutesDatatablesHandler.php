<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DungeonRoutesDatatablesHandler extends DatatablesHandler
{
    protected function calculateRecordsTotal(): int
    {
        // Clear them
        $countQuery = $this->_builder->getQuery()
            ->cloneWithout(['having', 'groups'])
            // ->cloneWithoutBindings(['select'])
            ->selectRaw('count(distinct dungeon_routes.id) as aggregate');
        // Get the count
        $result = $countQuery->get(['aggregate']);
        // Returns an array with numbers, sum the entries to get the actual count. Again, a hack but it works for now.
        $recordsTotal = 0;
        foreach ($result as $countResult) {
            $recordsTotal += $countResult->aggregate;
        }

        return $recordsTotal;
    }

//    protected function calculateRecordsFiltered(): int
//    {
//        // Count without limit first
//        // I tried with SQL_CALC_FOUND_ROWS but that doesn't really work with Laravel pumping out more queries,
//        // then FOUND_ROWS() would return the result from the wrong function, rather annoying that is.
//        // Bit of a hack, but for now the only way to reliably get the pre-limit count.
//        $query = $this->_builder->getQuery()
//            ->cloneWithout(['columns', 'offset', 'limit'])->cloneWithoutBindings(['select'])
//            ->selectRaw(DB::raw('count( distinct dungeon_routes.id) as aggregate'));
//        // Temp store; it messes with the count
//        $havings = $query->havings;
//        $query->havings = null;
//        $query->orders = null;
//        $countResults = $query->get();
//        // Restore
//        $query->havings = $havings;
//
//        // Returns an array with numbers, sum the entries to get the actual count. Again, a hack but it works for now.
//        $recordsFiltered = 0;
//        foreach ($countResults as $countResult) {
//            $recordsFiltered += $countResult->aggregate;
//        }
//
//        return $recordsFiltered;
//    }
}