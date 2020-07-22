<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables;

use Illuminate\Support\Facades\DB;

class UsersDatatablesHandler extends DatatablesHandler
{
    protected function calculateRecordsTotal(): int
    {
        $query = $this->_builder->getQuery()
            ->cloneWithout(['columns', 'offset', 'limit'])->cloneWithoutBindings(['select'])
            ->selectRaw(DB::raw('SQL_CALC_FOUND_ROWS *'));

        $havings = $query->havings;
        $query->havings = null;
        $query->orders = null;
        $countResults = $query->get();
        // Restore
        $query->havings = $havings;

        $foundRows = DB::select(DB::raw('SELECT FOUND_ROWS() as count'));
        return $foundRows[0]->count;
    }
}