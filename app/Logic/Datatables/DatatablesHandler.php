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

class DatatablesHandler
{

    /**
     * @var Request
     */
    private $_request;

    /**
     * @var Builder
     */
    private $_builder;

    /**
     * @var array
     */
    private $_columnHandlers;

    /**
     * @var int
     */
    private $_recordsTotal = 0;

    /**
     * @var int
     */
    private $_recordsFiltered = 0;

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
        // Store havings, since they may cause incorrect total results
        $havings = $this->_builder->getQuery()->havings;
        // Clear them
        $this->_builder->getQuery()->havings = null;
        // Get the count
        $this->_recordsTotal = $builder->count();
        // Restore havings
        $this->_builder->getQuery()->havings = $havings;

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

        // Count without limit first
        // I tried with SQL_CALC_FOUND_ROWS but that doesn't really work with Laravel pumping out more queries,
        // then FOUND_ROWS() would return the result from the wrong function, rather annoying that is.
        // Bit of a hack, but for now the only way to reliably get the pre-limit count.
        $query = $this->_builder->getQuery()
            ->cloneWithout(['columns', 'offset', 'limit'])->cloneWithoutBindings(['select'])
            ->selectRaw(DB::raw('count( distinct dungeon_routes.id) as aggregate'));
        // Temp store; it messes with the count
        $havings = $query->havings;
        $query->havings = null;
        $query->orders = null;
        $countResults = $query->get();
        // Restore
        $query->havings = $havings;

        // Returns an array with numbers, sum the entries to get the actual count. Again, a hack but it works for now.
        $count = 0;
        foreach ($countResults as $countResult) {
            $count += $countResult->aggregate;
        }

        // Fetch the data
        $data = $this->_builder->get();

        $result = [
            'draw' => (int)$this->_request->get('draw'),
            // Initial amount of records
            'recordsTotal' => $this->_recordsTotal,
            // The amount of records after filtering
            'data' => $data,
            // The amount of rows there would have been, if it were not for the limits
            'recordsFiltered' => $count,
            // Only show this info in dev instance
            'input' => $isDev ? $this->_request->toArray() : [],
            // Debug sql queries for optimization
            'queries' => $isDev ? DB::getQueryLog() : []
        ];

        return $result;
    }
}