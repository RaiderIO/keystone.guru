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
    /**
     * @var string[] The simple column handler may use these column names - the columns that are requested are coming
     * from the front end and cannot be trusted - so add a filter here.
     */
    const VALID_COLUMN_NAMES = [
        'id',
        'title',
        'public_key',
        'name',
        'email',
    ];

    public function __construct(DatatablesHandler $dtHandler, $columnName, $columnData = null)
    {
        parent::__construct($dtHandler, $columnName, $columnData);
    }

    protected function applyFilter(Builder $subBuilder, Builder $orderBuilder,  $columnData, $order, $generalSearch): void
    {
        // If the column name is not valid, ignore it entirely
        if (!in_array($this->getColumnName(), self::VALID_COLUMN_NAMES)) {
            return;
        }

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
                $orderBuilder->orderBy($this->getColumnData(), $order['dir'] === 'asc' ? 'asc' : 'desc');
            }
        }
    }
}
