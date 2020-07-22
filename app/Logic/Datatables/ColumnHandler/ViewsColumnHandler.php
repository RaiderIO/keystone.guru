<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables\ColumnHandler;

use App\Logic\Datatables\DatatablesHandler;
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
            $builder->addSelect(DB::raw('pv.views AS views'));

            $subQuery = DB::table('page_views')
                ->select('model_id', DB::raw('COUNT(distinct page_views.id) views'))
                ->where('model_class', DungeonRoute::class)
                ->groupBy('model_id');

            $builder->joinSub($subQuery, 'pv', function ($join)
            {
                /** @var $join JoinClause */
                $join->on('dungeon_routes.id', '=', 'pv.model_id');
            });
            $builder->orderBy('views', $order['dir'] === 'asc' ? 'asc' : 'desc');
        }
    }
}