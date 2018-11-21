<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic;

use App\Models\GameServerRegion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DungeonRouteAffixesColumnHandler extends DatatablesColumnHandler
{

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'affixes.id');
    }

    protected function _applyFilter(Builder $builder, $columnData, $order)
    {

        $affixes = $columnData['search']['value'];
        if (!empty($affixes)) {
            $affixIds = explode(',', $affixes);

            $builder->whereHas('affixes', function ($query) use (&$affixIds) {
                /** @var $query Builder */
                $query->whereIn('affix_groups.id', $affixIds);
            });
        }

        /** @var GameServerRegion $region */
        $region = GameServerRegion::getUserOrDefaultRegion();

        // Order by the current affix on top, otherwise by ID
        $currentAffixId = $region->getCurrentAffixGroup()->id;
        // In order to sort by another table, join it
        // $builder->leftJoin('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id');
        // Then sort by current affix ID on top, THEN sort by ID ascending
        $builder->orderByRaw(sprintf('(SELECT IF(COUNT(ag.affix_group_id) = 0, 10000, IF(ag.affix_group_id = %s, 10000, MIN(ag.affix_group_id))) 
            FROM dungeon_route_affix_groups ag WHERE ag.dungeon_route_id = dungeon_routes.id)',
            $currentAffixId
        ));

//        if ($order !== null) {
//            DB::enableQueryLog();
//            $builder->get();
//
//            dd(DB::getQueryLog());
//
//            // $builder->orderByRaw();
//        }
    }
}