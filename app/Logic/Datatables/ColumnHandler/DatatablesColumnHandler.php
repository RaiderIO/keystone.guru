<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables\ColumnHandler;

use App\Logic\Datatables\DatatablesHandler;
use Exception;
use Illuminate\Database\Eloquent\Builder;

abstract class DatatablesColumnHandler
{

    /** @var DatatablesHandler */
    private DatatablesHandler $dtHandler;

    /**  @var string */
    private string $columnName;

    /** @var string|null */
    private ?string $columnData;

    public function __construct(DatatablesHandler $dtHandler, string $columnName, string $columnData = null)
    {
        $this->dtHandler = $dtHandler;

        $this->columnName = $columnName;
        // If not set, just copy the column name
        $this->columnData = $columnData ?? $columnName;
    }

    /**
     * @param Builder $builder
     * @param $columnData
     * @param $order
     * @param $generalSearch
     * @return mixed
     */
    protected abstract function applyFilter(Builder $builder, $columnData, $order, $generalSearch);

    /**
     * @return DatatablesHandler
     */
    public function getDtHandler(): DatatablesHandler
    {
        return $this->dtHandler;
    }

    /**
     * @return string Gets the column name of the handler.
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * @return string|null Gets the column name of the handler.
     */
    public function getColumnData(): ?string
    {
        return $this->columnData;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function applyToBuilder(): self
    {
        $request = $this->dtHandler->getRequest();

        if (!$request->exists('columns')) {
            throw new Exception('Unable to find columns parameter in Request parameters');
        }

        $columns       = $request->get('columns');
        $order         = ($request->get('order', []))[0];
        $generalSearch = ($request->get('search'))['value'];

        // Find the column we should handle
        $column = null;
        // Find the index too; needed to handle sorting later on
        $columnIndex = -1;
        foreach ($columns as $index => $value) {
            if ($value['name'] === $this->columnName) {
                $column      = $value;
                $columnIndex = $index;
                break;
            }
        }

        // If the column we're supposed to represent is not found
        if ($column !== null) {
            // == intended
            $order = $order['column'] == $columnIndex ? $order : null;

            // Handle the filtering of this column
            $this->applyFilter($this->dtHandler->getBuilder(), $column, $order, $generalSearch);
            // throw new \Exception(sprintf("Unable to find column '%s' in Request->params->columns array", $this->_columnName));
        }


        return $this;
    }
}
