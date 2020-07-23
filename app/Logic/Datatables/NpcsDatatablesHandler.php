<?php
/**
 * Created by PhpStorm.
 * User: wouterkoppenol
 * Date: 23-07-2020
 * Time: 13:46
 */

namespace App\Logic\Datatables;

use Illuminate\Support\Facades\DB;

class NpcsDatatablesHandler extends DatatablesHandler
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
    
    protected function calculateRecordsFiltered(): ?int
    {
        return null;
    }
}