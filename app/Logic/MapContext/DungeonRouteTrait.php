<?php


namespace App\Logic\MapContext;

use App\Models\AffixGroup;
use App\Models\DungeonRoute;

/**
 * Trait DungeonRouteTrait
 * @package App\Logic\MapContext
 *
 * @mixin MapContext
 */
trait DungeonRouteTrait
{
    /**
     * @param DungeonRoute $dungeonRoute
     * @return array
     */
    private function getDungeonRouteProperties(DungeonRoute $dungeonRoute): array
    {
        return [
            'publicKey'               => $dungeonRoute->public_key,
            'teamId'                  => $dungeonRoute->team_id,
            'pullGradient'            => $dungeonRoute->pull_gradient,
            'pullGradientApplyAlways' => $dungeonRoute->pull_gradient_apply_always,
            'faction'                 => strtolower($dungeonRoute->faction->name),
            'enemyForces'             => $dungeonRoute->enemy_forces,

            // Relations
            'killZones'               => $dungeonRoute->killzones,
            'mapIcons'                => $dungeonRoute->mapicons,
            'paths'                   => $dungeonRoute->paths,
            'brushlines'              => $dungeonRoute->brushlines,
            'pridefulenemies'         => $dungeonRoute->pridefulenemies,
            // A list of affixes that this route has (not to be confused with AffixGroups)
            'uniqueAffixes'           => $dungeonRoute->affixes->map(function (AffixGroup $affixGroup)
            {
                return $affixGroup->affixes;
            })->collapse()->unique()->pluck(['name'])->map(function(string $name){
                return __($name);
            })
        ];
    }
}