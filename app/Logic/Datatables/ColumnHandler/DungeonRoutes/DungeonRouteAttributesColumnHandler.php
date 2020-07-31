<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables\ColumnHandler\DungeonRoutes;

use App\Logic\Datatables\ColumnHandler\DatatablesColumnHandler;
use App\Logic\Datatables\DatatablesHandler;
use App\Models\RouteAttribute;
use Illuminate\Database\Eloquent\Builder;

class DungeonRouteAttributesColumnHandler extends DatatablesColumnHandler
{

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'routeattributes.name');
    }

    protected function _applyFilter(Builder $builder, $columnData, $order, $generalSearch)
    {
        $routeattributes = $columnData['search']['value'];
        // If filtering or ordering
        if (!empty($routeattributes) || $order !== null) {
            // $builder->leftJoin('dungeon_route_attributes', 'dungeon_route_attributes.dungeon_route_id', '=', 'dungeon_routes.id');
            $builder->groupBy('dungeon_routes.id');
        }

        // If filtering
        if (!empty($routeattributes)) {
            $allRouteAttributeIds = RouteAttribute::all()->pluck('id')->toArray();
            $routeAttributeIds = explode(',', $routeattributes);

            // Compute the attribute IDs that the user does NOT want
            $invalidAttributeIds = array_diff($allRouteAttributeIds, $routeAttributeIds);

            $filterFn = function ($query) use (&$invalidAttributeIds, &$routeAttributeIds)
            {
                /** @var $query Builder */
                $query->whereIn('dungeon_route_attributes.route_attribute_id', $invalidAttributeIds);
            };

            // If we should account for dungeon routes having no attributes
            if (in_array(-1, $routeAttributeIds)) {
                // Wrap this in a where so both these statements get brackets around them
                $builder->where(function ($query) use (&$filterFn)
                {
                    /** @var $query Builder */
                    // May not have attributes at all
                    $query->whereHas('routeattributes', null, '=', 0);
                    $query->orWhereHas('routeattributes', $filterFn, '=', 0);
                });
            } else {
                // Must have attributes
                $builder->whereHas('routeattributes');
                // But may not have some specific attributes
                $builder->whereHas('routeattributes', $filterFn, '=', 0);
            }

        }

        // If ordering
        if ($order !== null) {
            $builder->orderByRaw('COUNT(dungeon_route_attributes.id) ' . ($order['dir'] === 'asc' ? 'asc' : 'desc'));
        }
    }
}