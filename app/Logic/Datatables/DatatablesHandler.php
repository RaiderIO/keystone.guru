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
        $this->_recordsTotal = $builder->count();

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

        // @TODO Fix this workaround. This is done because doing a count when sorting for ratings gives an SQL error
        // The count SQL (imho) incorrectly adds a order by which does not exist because it deletes the column that it's
        // sorting on when injecting its borked COUNT statement in the select.
        // The solution in this case is to temporarily remove the order by statement from the builder, do the count,
        // and then restore it. The error was:
        // SQLSTATE[42S22]: Column not found: 1054 Unknown column 'avg_rating' in 'order clause' (SQL: select count(*) as aggregate from `dungeon_routes` left join `dungeon_route_ratings` on `dungeon_route_id` = `dungeon_routes`.`id` where `unlisted` = 0 and `demo` = 0 and exists (select * from `dungeons` where `dungeon_routes`.`dungeon_id` = `dungeons`.`id` and `active` = 1) and `published` = 1 group by dungeon_routes.id order by `avg_rating` asc limit 25 offset 0)

        $query = $this->_builder->getQuery();
        $oldOrders = $query->orders;
        $query->orders = null;
        $count = $this->_builder->count();
        $query->orders = $oldOrders;

        $result = [
            'draw' => (int)$this->_request->get('draw'),
            // Initial amount of records
            'recordsTotal' => $this->_recordsTotal,
            // The amount of records after filtering
            'recordsFiltered' => $count,
            'data' => $this->_builder->get(),
            'input' => $this->_request->toArray()
        ];

        return $result;
    }
}