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
    private readonly ?string $columnData;

    public function __construct(
        private readonly DatatablesHandler $dtHandler,
        private readonly string            $columnName,
        ?string                            $columnData = null,
    ) {
        // If not set, just copy the column name
        $this->columnData = $columnData ?? $this->columnName;
    }

    /**
     * @param  Builder $subBuilder
     * @param  Builder $orderBuilder
     * @param          $columnData
     * @param          $order
     * @param          $generalSearch
     * @return void
     */
    abstract protected function applyFilter(
        Builder $subBuilder,
        Builder $orderBuilder,
                $columnData,
                $order,
                $generalSearch,
    ): void;

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
     *
     * @throws Exception
     */
    public function applyToBuilder(Builder $subBuilder, Builder $orderBuilder): self
    {
        $request = $this->dtHandler->getRequest();

        if (!$request->exists('columns')) {
            throw new Exception('Unable to find columns parameter in Request parameters');
        }

        $columns = $request->get('columns');
        $order   = $request->get('order');
        if (is_array($order)) {
            $order = $order[0] ?? null;
        }
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

        // If the column we're supposed to represent is found
        if ($column !== null) {
            // == intended
            $order = is_null($order) ? null : ($order['column'] == $columnIndex ? $order : null);

            // Handle the filtering of this column
            $this->applyFilter($subBuilder, $orderBuilder, $column, $order, $generalSearch);
            // throw new \Exception(sprintf("Unable to find column '%s' in Request->params->columns array", $this->_columnName));
        }

        return $this;
    }
}
