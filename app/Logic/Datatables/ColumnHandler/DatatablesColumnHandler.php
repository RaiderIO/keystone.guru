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

abstract class DatatablesColumnHandler
{

    /** @var DatatablesHandler */
    private $_dtHandler;

    /**  @var string */
    private $_columnName;

    /** @var string */
    private $_columnData;

    public function __construct(DatatablesHandler $dtHandler, string $columnName, string $columnData = null)
    {
        $this->_dtHandler = $dtHandler;

        $this->_columnName = $columnName;
        // If not set, just copy the column name
        $this->_columnData = $columnData ?? $columnName;
    }

    protected abstract function _applyFilter(Builder $builder, $columnData, $order, $generalSearch);

    /**
     * @return DatatablesHandler
     */
    public function getDtHandler(): DatatablesHandler
    {
        return $this->_dtHandler;
    }

    /**
     * @return string Gets the column name of the handler.
     */
    public function getColumnName()
    {
        return $this->_columnName;
    }

    /**
     * @return string Gets the column name of the handler.
     */
    public function getColumnData()
    {
        return $this->_columnData;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function applyToBuilder()
    {
        $request = $this->_dtHandler->getRequest();

        if (!$request->exists('columns')) {
            throw new \Exception('Unable to find columns parameter in Request parameters');
        }

        $columns = $request->get('columns');
        $order = ($request->get('order', []))[0];
        $generalSearch = ($request->get('search'))['value'];

        // Find the column we should handle
        $column = null;
        // Find the index too; needed to handle sorting later on
        $columnIndex = -1;
        foreach ($columns as $index => $value) {
            if ($value['name'] === $this->_columnName) {
                $column = $value;
                $columnIndex = $index;
                break;
            }
        }

        // If the column we're supposed to represent is not found
        if ($column !== null) {
            // == intended
            $order = $order['column'] == $columnIndex ? $order : null;

            // Handle the filtering of this column
            $this->_applyFilter($this->_dtHandler->getBuilder(), $column, $order, $generalSearch);
            // throw new \Exception(sprintf("Unable to find column '%s' in Request->params->columns array", $this->_columnName));
        }


        return $this;
    }
}