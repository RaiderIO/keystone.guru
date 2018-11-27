<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DungeonRouteAttributesColumnHandler extends DatatablesColumnHandler
{

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'routeattributes.name');
    }

    protected function _applyFilter(Builder $builder, $columnData, $order)
    {

        $routeattributes = $columnData['search']['value'];
        // If filtering or ordering
        if (!empty($routeattributes) || $order !== null) {
            $builder->leftJoin('dungeon_route_attributes', 'dungeon_route_id', '=', 'dungeon_routes.id');
            $builder->groupBy('dungeon_routes.id');
        }

        // If filtering
        if (!empty($routeattributes)) {
            $routeAttributeIds = explode(',', $routeattributes);

            $builder->whereHas('routeattributes', function ($query) use (&$routeAttributeIds) {
                /** @var $query Builder */
                $query->whereIn('route_attributes.id', $routeAttributeIds);

                if( in_array(-1, $routeAttributeIds) ){
                    $query->orWhere('route_attributes.id', '=', null);
                }
            });
        }

        // If ordering
        if ($order !== null) {
            $builder->orderByRaw('COUNT(dungeon_route_attributes.id) ' . ($order['dir'] === 'asc' ? 'asc' : 'desc'));
        }

//        DB::enableQueryLog();
//        $builder->get();
//        dd(DB::getQueryLog());
    }
}