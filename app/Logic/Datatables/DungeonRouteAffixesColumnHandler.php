<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables;

use App\Service\Season\SeasonService;
use Illuminate\Database\Eloquent\Builder;

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

        // Only order
        if ($order !== null) {
            $seasonService = resolve(SeasonService::class);

            // Order by the current affix on top, otherwise by ID
            $currentAffixId = $seasonService->getCurrentSeason()->getCurrentAffixGroup()->id;
            // In order to sort by another table, join it
            // $builder->leftJoin('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id');
            // Then sort by current affix ID on top, THEN sort by ID ascending
            if ($order['dir'] === 'asc') {
                $builder->orderByRaw(sprintf('(select if(MIN(ag.affix_group_id) is null, 10000, if(ag.affix_group_id = %s, -1, MIN(ag.affix_group_id))) 
                    from dungeon_route_affix_groups ag where ag.dungeon_route_id = dungeon_routes.id)',
                    $currentAffixId
                ));
            } else {
                $builder->orderByRaw(sprintf('(select if(MIN(ag.affix_group_id) is null, -1, if(ag.affix_group_id = %s, 10000, MAX(ag.affix_group_id))) 
                    from dungeon_route_affix_groups ag where ag.dungeon_route_id = dungeon_routes.id)',
                    $currentAffixId
                ));
            }
        }
    }
}