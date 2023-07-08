<?php


namespace App\Logic\MapContext;

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteEnemyRaidMarker;
use Illuminate\Support\Collection;

/**
 * Trait DungeonRouteTrait
 * @package App\Logic\MapContext
 *
 * @mixin MapContext
 */
trait DungeonRouteProperties
{
    /**
     * @param array $publicKeys
     * @return Collection
     */
    private function getDungeonRoutesProperties(array $publicKeys): Collection
    {
        $result = collect();

        /** @var Collection|DungeonRoute $dungeonRoutes */
        $dungeonRoutes = DungeonRoute::with([
            'killZones',
            'mapicons',
            'paths',
            'brushlines',
            'pridefulEnemies',
            'enemyRaidMarkers',
        ])->whereIn('public_key', $publicKeys)->get();

        foreach ($dungeonRoutes as $dungeonRoute) {
            $result->put($dungeonRoute->public_key, $this->getDungeonRouteProperties($dungeonRoute));
        }

        return $result;
    }

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
            'faction'                 => $dungeonRoute->faction->key,
            'enemyForces'             => $dungeonRoute->enemy_forces,
            'levelMin'                => $dungeonRoute->level_min,
            'levelMax'                => $dungeonRoute->level_max,
            'dungeonDifficulty'       => $dungeonRoute->dungeon_difficulty,

            'mappingVersionUpgradeUrl' => route('dungeonroute.upgrade', [
                'dungeon'      => $dungeonRoute->dungeon,
                'dungeonroute' => $dungeonRoute,
                'title'        => $dungeonRoute->getTitleSlug(),
            ]),

            // Relations
            'killZones'                => $dungeonRoute->killZones,
            'mapIcons'                 => $dungeonRoute->mapicons,
            'paths'                    => $dungeonRoute->paths,
            'brushlines'               => $dungeonRoute->brushlines,
            'pridefulEnemies'          => $dungeonRoute->pridefulEnemies,
            'enemyRaidMarkers'         => $dungeonRoute->enemyRaidMarkers->map(function (DungeonRouteEnemyRaidMarker $drEnemyRaidMarker) {
                return [
                    'enemy_id'         => $drEnemyRaidMarker->enemy_id,
                    'raid_marker_name' => $drEnemyRaidMarker->raidMarker->name,
                ];
            }),
            // A list of affixes that this route has (not to be confused with AffixGroups)
            'uniqueAffixes'            => $dungeonRoute->affixes->map(function (AffixGroup $affixGroup) {
                return $affixGroup->affixes;
            })->collapse()->unique()->pluck(['name'])->map(function (string $name) {
                return __($name, [], 'en-US');
            }),
        ];
    }
}