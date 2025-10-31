<?php

namespace App\Logic\MapContext\Map;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Logic\MapContext\Map\MapContextBase;
use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;

/**
 * Class MapContextDungeonRoute
 *
 * @author  Wouter
 *
 * @since   06/08/2020
 */
class MapContextDungeonRoute extends MapContextBase
{
    use ListsEnemies;

    public function __construct(
        CacheServiceInterface       $cacheService,
        CoordinatesServiceInterface $coordinatesService,
        private DungeonRoute        $dungeonRoute,
        string                      $mapFacadeStyle,
    ) {
        parent::__construct($cacheService, $coordinatesService, $dungeonRoute->dungeon, $dungeonRoute->mappingVersion, $mapFacadeStyle);
    }

    public function getVisibleFloors(): array
    {
        return $this->dungeonRoute->dungeon->floorsForMapFacade(
            $this->dungeonRoute->mappingVersion,
            $this->mapFacadeStyle === User::MAP_FACADE_STYLE_FACADE,
        )->active()->get()->toArray();
    }

    public function getType(): string
    {
        return 'dungeonroute';
    }

    public function isTeeming(): bool
    {
        return $this->dungeonRoute->teeming;
    }

    public function getSeasonalIndex(): int
    {
        return $this->dungeonRoute->seasonal_index;
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-route-edit.%s', config('app.type'), $this->dungeonRoute->getRouteKey());
    }

    protected function getEnemies(): ?array
    {
        // Do not override the enemies
        return null;
    }

    public function toArray(): array
    {
        $useFacade = $this->mapFacadeStyle === User::MAP_FACADE_STYLE_FACADE;

        return array_merge(parent::toArray(), [
            'publicKey'               => $this->dungeonRoute->public_key,
            'teamId'                  => $this->dungeonRoute->team_id,
            'description'             => $this->dungeonRoute->description,
            'pullGradient'            => $this->dungeonRoute->pull_gradient,
            'pullGradientApplyAlways' => $this->dungeonRoute->pull_gradient_apply_always,
            'faction'                 => $this->dungeonRoute->faction->key,
            'enemyForces'             => $this->dungeonRoute->enemy_forces,
            'levelMin'                => $this->dungeonRoute->level_min ?? config('keystoneguru.keystone.levels.default_min'),
            'levelMax'                => $this->dungeonRoute->level_max ?? config('keystoneguru.keystone.levels.default_max'),
            'dungeonDifficulty'       => $this->dungeonRoute->dungeon_difficulty,

            'mappingVersionUpgradeUrl' => route('dungeonroute.upgrade', [
                'dungeon'      => $this->dungeonRoute->dungeon,
                'dungeonroute' => $this->dungeonRoute,
                'title'        => $this->dungeonRoute->getTitleSlug(),
            ]),

            // Relations
            'killZones'        => $this->dungeonRoute->mapContextKillZones($this->coordinatesService, $useFacade),
            'mapIcons'         => $this->dungeonRoute->mapContextMapIcons($this->coordinatesService, $useFacade),
            'paths'            => $this->dungeonRoute->mapContextPaths($this->coordinatesService, $useFacade),
            'brushlines'       => $this->dungeonRoute->mapContextBrushlines($this->coordinatesService, $useFacade),
            'pridefulEnemies'  => $this->dungeonRoute->pridefulEnemies,
            'enemyRaidMarkers' => $this->dungeonRoute->enemyRaidMarkers->map(static fn(
                DungeonRouteEnemyRaidMarker $drEnemyRaidMarker,
            ) => [
                'enemy_id'         => $drEnemyRaidMarker->enemy_id,
                'raid_marker_name' => $drEnemyRaidMarker->raidMarker->name,
            ]),
            // A list of affixes that this route has (not to be confused with AffixGroups)
            'uniqueAffixes' => $this->dungeonRoute->affixes
                ->map(static fn(AffixGroup $affixGroup) => $affixGroup->affixes)
                ->collapse()
                ->unique()
                ->pluck(['name'])
                ->map(static fn(string $name) => __($name, [], 'en_US')),
            // Used for showing a modal when the route has been deleted while editing
            'dungeonRouteClass' => DungeonRoute::class,
        ]);
    }
}
