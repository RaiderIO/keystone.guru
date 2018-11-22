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
            // weighted rating (WR) = (v ÷ (v+m)) × R + (m ÷ (v+m)) × C , where:
            //
            //* R = average for the movie (mean) = (Rating)
            //* v = number of votes for the movie = (votes)
            //* m = minimum votes required to be listed in the Top 250 (currently 3000)
            //* C = the mean vote across the whole report (currently 6.9)
            $builder->addSelect(DB::raw('AVG(dungeon_route_ratings.rating) as avg_rating'));

            $builder->leftJoin('dungeon_route_ratings', 'dungeon_route_id', '=', 'dungeon_routes.id');
            $builder->groupBy(DB::raw('dungeon_routes.id'));
            $builder->orderBy('avg_rating', $order['dir'] === 'asc' ? 'asc' : 'desc');
        }
//
//        DB::enableQueryLog();
//
//        $builder->get();
//
//        dd(DB::getQueryLog());
    }
}