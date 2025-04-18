<?php

namespace App\Logic\MapContext;

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

/**
 * Trait DungeonRouteTrait
 *
 * @mixin MapContext
 */
trait DungeonRouteProperties
{
    public function getFloors(): Collection
    {
        $useFacade = $this->getMapFacadeStyle() === 'facade';

        return $this->floor->dungeon->floorsForMapFacade($this->mappingVersion, $useFacade)->active()->get();
    }

    /**
     * @return Collection<DungeonRoute>
     */
    private function getDungeonRoutesProperties(CoordinatesServiceInterface $coordinatesService, array $publicKeys): Collection
    {
        $result = collect();

        /** @var Collection<DungeonRoute> $dungeonRoutes */
        $dungeonRoutes = DungeonRoute::with([
            'killZones',
            'mapicons',
            'paths',
            'brushlines',
            'pridefulEnemies',
            'enemyRaidMarkers',
        ])->whereIn('public_key', $publicKeys)->get();

        foreach ($dungeonRoutes as $dungeonRoute) {
            $result->put($dungeonRoute->public_key, $this->getDungeonRouteProperties($coordinatesService, $dungeonRoute));
        }

        return $result;
    }

    private function getDungeonRouteProperties(CoordinatesServiceInterface $coordinatesService, DungeonRoute $dungeonRoute): array
    {
        $useFacade = $this->getMapFacadeStyle() === 'facade';

        return [
            'publicKey'               => $dungeonRoute->public_key,
            'teamId'                  => $dungeonRoute->team_id,
            'description'             => $dungeonRoute->description,
            'pullGradient'            => $dungeonRoute->pull_gradient,
            'pullGradientApplyAlways' => $dungeonRoute->pull_gradient_apply_always,
            'faction'                 => $dungeonRoute->faction->key,
            'enemyForces'             => $dungeonRoute->enemy_forces,
            'levelMin'                => $dungeonRoute->level_min ?? config('keystoneguru.keystone.levels.default_min'),
            'levelMax'                => $dungeonRoute->level_max ?? config('keystoneguru.keystone.levels.default_max'),
            'dungeonDifficulty'       => $dungeonRoute->dungeon_difficulty,

            'mappingVersionUpgradeUrl' => route('dungeonroute.upgrade', [
                'dungeon'      => $dungeonRoute->dungeon,
                'dungeonroute' => $dungeonRoute,
                'title'        => $dungeonRoute->getTitleSlug(),
            ]),

            // Relations
            'killZones'                => $dungeonRoute->mapContextKillZones($coordinatesService, $useFacade),
            'mapIcons'                 => $dungeonRoute->mapContextMapIcons($coordinatesService, $useFacade),
            'paths'                    => $dungeonRoute->mapContextPaths($coordinatesService, $useFacade),
            'brushlines'               => $dungeonRoute->mapContextBrushlines($coordinatesService, $useFacade),
            'pridefulEnemies'          => $dungeonRoute->pridefulEnemies,
            'enemyRaidMarkers'         => $dungeonRoute->enemyRaidMarkers->map(static fn(DungeonRouteEnemyRaidMarker $drEnemyRaidMarker) => [
                'enemy_id'         => $drEnemyRaidMarker->enemy_id,
                'raid_marker_name' => $drEnemyRaidMarker->raidMarker->name,
            ]),
            // A list of affixes that this route has (not to be confused with AffixGroups)
            'uniqueAffixes'            => $dungeonRoute->affixes
                ->map(static fn(AffixGroup $affixGroup) => $affixGroup->affixes)
                ->collapse()
                ->unique()
                ->pluck(['name'])
                ->map(static fn(string $name) => __($name, [], 'en_US')),
            // Used for showing a modal when the route has been deleted while editing
            'dungeonRouteClass'        => DungeonRoute::class,
        ];
    }
}
