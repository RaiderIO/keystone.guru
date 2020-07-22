<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables;

use App\Logic\Datatables\ColumnHandler\DatatablesColumnHandler;
use App\Logic\Datatables\ColumnHandler\SimpleColumnHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class DatatablesHandler
{

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var Builder
     */
    protected $_builder;

    /**
     * @var array
     */
    protected $_columnHandlers;

    public function __construct(Request $request)
    {
        $this->_request = $request;
        $this->_columnHandlers = [];
    }

    /**
     * @return Request
     */
    function getRequest()
    {
        return $this->_request;
    }

    /**
     * @return Builder
     */
    function getBuilder()
    {
        return $this->_builder;
    }

    /**
     * @param Builder $builder
     * @return $this
     */
    public function setBuilder(Builder $builder)
    {
        $this->_builder = $builder;

        return $this;
    }

    /**
     * @param DatatablesColumnHandler|array $dtColumnHandlers
     * @return $this
     */
    public function addColumnHandler($dtColumnHandlers = [])
    {
        if (!is_array($dtColumnHandlers)) {
            $dtColumnHandlers = [$dtColumnHandlers];
        }

        foreach ($dtColumnHandlers as $handler) {
            /** @var $handler DatatablesColumnHandler */
            $this->_columnHandlers[$handler->getColumnName()] = $handler;
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function applyRequestToBuilder()
    {
        // Set limits
        $this->_builder->offset((int)$this->_request->get('start'));
        $this->_builder->limit((int)$this->_request->get('length'));

        // For any custom column handlers, handle their wishes
        foreach ($this->_columnHandlers as $columnHandler) {
            $columnHandler->applyToBuilder();
        }

        // Handle default filtering/sorting
        $columns = $this->_request->get('columns', []);
        foreach ($columns as $column) {
            $columnName = $column['name'];
            // Only if the column name was set
            if (!empty($columnName)) {
                // Only if not handled by a custom column handler
                if (!isset($this->_columnHandlers[$columnName])) {
                    // Handle filtering/sorting by this column
                    (new SimpleColumnHandler($this, $columnName))->applyToBuilder();
                }
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        $isDev = config('app.env') !== 'production';
        if ($isDev) {
            DB::enableQueryLog();
        }

        // Fetch the data
        $data = $this->_builder->get();

        $result = [
            'draw'            => (int)$this->_request->get('draw'),
            // Initial amount of records
            'recordsTotal'    => $this->calculateRecordsTotal(),
            // The amount of records after filtering
            'data'            => $data,
            // The amount of rows there would have been, if it were not for the limits
            'recordsFiltered' => $data->count(),
            // Only show this info in dev instance
            'input'           => $isDev ? $this->_request->toArray() : [],
            // Debug sql queries for optimization
            'queries'         => $isDev ? DB::getQueryLog() : []
        ];

        if ($isDev) {
            DB::disableQueryLog();
        }

        return $result;
    }

    protected abstract function calculateRecordsTotal(): int;
}