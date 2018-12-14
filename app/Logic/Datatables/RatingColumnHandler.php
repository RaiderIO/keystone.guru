<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables;

use App\Models\GameServerRegion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RatingColumnHandler extends DatatablesColumnHandler
{

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'rating');
    }

    protected function _applyFilter(Builder $builder, $columnData, $order)
    {

        $rating = $columnData['search']['value'];
        if (!empty($rating)) {
//            $builder->whereHas('affixes', function ($query) use (&$affixIds) {
//                /** @var $query Builder */
//                $query->whereIn('affix_groups.id', $affixIds);
//            });
        }

        // Only order
        if ($order !== null) {
            // https://stackoverflow.com/a/1881185/771270
            // I divided by 2 to reduce the impact of single 10 votes when there's not a lot of voting (which is the case now)
            // This may have to be revisited once the site gains some more traction and votes start pouring in
            $builder->addSelect(DB::raw('(AVG(dungeon_route_ratings.rating) / 2) + LOG(COUNT(dungeon_route_ratings.id)) as weighted_rating'));

            $builder->leftJoin('dungeon_route_ratings', 'dungeon_route_ratings.dungeon_route_id', '=', 'dungeon_routes.id');
            $builder->groupBy(DB::raw('dungeon_routes.id'));
            $builder->orderBy('weighted_rating', $order['dir'] === 'asc' ? 'asc' : 'desc');
        }
    }
}