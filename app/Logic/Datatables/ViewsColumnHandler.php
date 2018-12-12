<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables;

use App\Models\DungeonRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class ViewsColumnHandler extends DatatablesColumnHandler
{

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'views');
    }

    protected function _applyFilter(Builder $builder, $columnData, $order)
    {
        $views = $columnData['search']['value'];
        if (!empty($views)) {
//            $builder->whereHas('affixes', function ($query) use (&$affixIds) {
//                /** @var $query Builder */
//                $query->whereIn('affix_groups.id', $affixIds);
//            });
        }

        // Only order
        if ($order !== null) {
            $builder->addSelect(DB::raw('COUNT(page_views.id) as views'));

            $builder->leftJoin('dungeon_route_ratings', function ($join) {
                /** @var $join JoinClause */
                $join->on('page_views.model_id', '=', 'dungeon_routes.id');
                $join->on('page_views.model_class', '=', DungeonRoute::class);
            });
            $builder->groupBy(DB::raw('page_views.model_id'));
            $builder->orderBy('views', $order['dir'] === 'asc' ? 'asc' : 'desc');
        }
    }
}