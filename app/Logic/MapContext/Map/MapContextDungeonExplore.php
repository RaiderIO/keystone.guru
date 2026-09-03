<?php

namespace App\Logic\MapContext\Map;

use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\Dtos\SeasonWeek;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Override;

/**
 * Class MapContextDungeonExplore
 *
 * @author  Wouter
 *
 * @since   28/08/2023
 */
class MapContextDungeonExplore extends MapContextMappingVersion
{
    public function __construct(
        CacheServiceInterface                             $cacheService,
        CoordinatesServiceInterface                       $coordinatesService,
        private readonly SeasonServiceInterface           $seasonService,
        private readonly SeasonAffixGroupServiceInterface $seasonAffixGroupService,
        Dungeon                                           $dungeon,
        MappingVersion                                    $mappingVersion,
        string                                            $mapFacadeStyle,
    ) {
        parent::__construct($cacheService, $coordinatesService, $dungeon, $mappingVersion, $mapFacadeStyle);
    }

    /**
     * @return array<string, mixed>
     */
    public function getVisibleFloors(): array
    {
        return $this->dungeon->floorsForMapFacade(
            $this->mappingVersion,
            $this->mapFacadeStyle === User::MAP_FACADE_STYLE_FACADE,
        )->active()->get()->toArray();
    }

    public function getType(): string
    {
        return 'dungeonExplore';
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-dungeon-explore.%s', config('app.type'), $this->dungeon->getRouteKey());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEnemies(): ?array
    {
        // Do not override the enemies
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        $activeSeason = $this->dungeon->getActiveSeason($this->seasonService);

        return array_merge(parent::toArray(), [
            'featuredAffixes' => $activeSeason == null ? [] : $this->seasonAffixGroupService->getFeaturedAffixes($activeSeason),
            'seasonWeeks'     => $this->getSeasonWeeks(),
        ]);
    }

    /**
     * The week index to keystone leaderboard period mapping the heatmap's week filter translates its selection
     * with. Resolved against the most recent season rather than getActiveSeason(), because that is the season the
     * week dropdown's options are built from - getActiveSeason() prefers an upcoming season, whose weeks are not
     * in the dropdown at all.
     *
     * @return array<int, array{week: int, period: int}>
     */
    private function getSeasonWeeks(): array
    {
        $season = $this->seasonService->getMostRecentSeasonForDungeon($this->dungeon);

        if ($season === null) {
            return [];
        }

        return $this->seasonService->getSeasonWeeks($season, GameServerRegion::getUserOrDefaultRegion())
            ->map(static fn(SeasonWeek $seasonWeek): array => [
                'week'   => $seasonWeek->week,
                'period' => $seasonWeek->period,
            ])
            ->values()
            ->all();
    }
}
