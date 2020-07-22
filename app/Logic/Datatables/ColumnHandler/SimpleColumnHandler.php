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

    public function __construct(DatatablesHandler $dtHandler, $columnName)
    {
        parent::__construct($dtHandler, $columnName);
    }

    protected function _applyFilter(Builder $builder, $columnData, $order)
    {
        // If we should search for this value
        if ($columnData['searchable'] === 'true') {
            $searchValue = $columnData['search']['value'];
            if (!empty($searchValue)) {
                $builder->where($this->getColumnName(), $searchValue);
            }
        }

        // If we should order
        if ($columnData['orderable'] === 'true') {
            // Order on this column?
            if (!is_null($order)) {
                // Order either asc or desc, nothing else
                $builder->orderBy($this->getColumnName(), $order['dir'] === 'asc' ? 'asc' : 'desc');
            }
        }
    }
}