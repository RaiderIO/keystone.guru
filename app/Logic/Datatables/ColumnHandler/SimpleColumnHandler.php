<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables\ColumnHandler;

use App\Logic\Datatables\DatatablesHandler;
use Illuminate\Database\Eloquent\Builder;

class SimpleColumnHandler extends DatatablesColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler, $columnName, $columnData = null)
    {
        parent::__construct($dtHandler, $columnName, $columnData);
    }

    protected function applyFilter(Builder $subBuilder, $columnData, $order, $generalSearch)
    {
        // If we should search for this value
        if ($columnData['searchable'] === 'true') {
            $searchValue = $columnData['search']['value'] ?? $generalSearch;
            if (!empty($searchValue)) {
                $subBuilder->orWhere($this->getColumnData(), 'LIKE', sprintf('%%%s%%', $searchValue));
            }
        }

        // If we should order
        if ($columnData['orderable'] === 'true') {
            // Order on this column?
            if (!is_null($order)) {
                // Order either asc or desc, nothing else
                $subBuilder->orderBy($this->getColumnData(), $order['dir'] === 'asc' ? 'asc' : 'desc');
            }
        }
    }
}
