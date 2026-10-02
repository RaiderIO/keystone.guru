<?php

namespace App\Service\DungeonStart;

use App\Models\Dungeon;
use App\Models\DungeonStart;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\DungeonStartRepositoryInterface;
use App\Repositories\Interfaces\GameVersion\GameVersionRepositoryInterface;
use App\Service\DungeonStart\Dtos\DungeonStartNavigation;
use Illuminate\Support\Collection;

class DungeonStartNavigationService implements DungeonStartNavigationServiceInterface
{
    public function __construct(
        private readonly DungeonStartRepositoryInterface $dungeonStartRepository,
        private readonly GameVersionRepositoryInterface  $gameVersionRepository,
    ) {
    }

    public function resolveNavigation(DungeonStart $dungeonStart, GameVersion $gameVersion): ?DungeonStartNavigation
    {
        if ($dungeonStart->target_dungeon_id !== null) {
            return $this->resolveTargetNavigation($dungeonStart->targetDungeon, $gameVersion);
        }

        return $this->resolveBackLinkNavigation($dungeonStart->floor->dungeon, $gameVersion);
    }

    public function getNavigationsForMappingVersion(MappingVersion $mappingVersion, GameVersion $gameVersion): Collection
    {
        $dungeonStarts = $mappingVersion->dungeonStarts()
            ->with(['floor.dungeon', 'targetDungeon'])
            ->get();

        /** @var array<int, DungeonStartNavigation|null> $backLinkNavigationByDungeonId */
        $backLinkNavigationByDungeonId = [];

        $result = collect();
        foreach ($dungeonStarts as $dungeonStart) {
            if ($dungeonStart->target_dungeon_id !== null) {
                $navigation = $this->resolveTargetNavigation($dungeonStart->targetDungeon, $gameVersion);
            } else {
                $dungeon    = $dungeonStart->floor->dungeon;
                $navigation = array_key_exists($dungeon->id, $backLinkNavigationByDungeonId) ?
                    $backLinkNavigationByDungeonId[$dungeon->id] :
                    $backLinkNavigationByDungeonId[$dungeon->id] = $this->resolveBackLinkNavigation($dungeon, $gameVersion);
            }

            if ($navigation !== null) {
                $result->put($dungeonStart->id, $navigation);
            }
        }

        return $result;
    }

    private function resolveTargetNavigation(?Dungeon $targetDungeon, GameVersion $gameVersion): ?DungeonStartNavigation
    {
        if ($targetDungeon === null) {
            return null;
        }

        $resolvedGameVersion = $this->resolveGameVersion($targetDungeon, $gameVersion);
        if ($resolvedGameVersion === null) {
            return null;
        }

        return new DungeonStartNavigation(false, $resolvedGameVersion, $targetDungeon, null);
    }

    private function resolveBackLinkNavigation(Dungeon $dungeon, GameVersion $gameVersion): ?DungeonStartNavigation
    {
        foreach ($this->dungeonStartRepository->getDungeonStartsTargetingDungeon($dungeon->id) as $linkingDungeonStart) {
            $linkingDungeon = $linkingDungeonStart->floor->dungeon;
            if ($linkingDungeon->id === $dungeon->id) {
                continue;
            }

            $resolvedGameVersion = $this->resolveGameVersion($linkingDungeon, $gameVersion);
            if ($resolvedGameVersion === null) {
                continue;
            }

            // Starts left behind in older mapping versions must not link back
            $currentMappingVersion = $linkingDungeon->getCurrentMappingVersionForGameVersion($resolvedGameVersion);
            if ($currentMappingVersion?->id !== $linkingDungeonStart->mapping_version_id) {
                continue;
            }

            return new DungeonStartNavigation(true, $resolvedGameVersion, $linkingDungeon, $linkingDungeonStart->floor);
        }

        return null;
    }

    /**
     * The game version to open the dungeon in: the one being viewed if the dungeon has mapping there, otherwise the
     * first game version the dungeon does have mapping in.
     */
    private function resolveGameVersion(Dungeon $dungeon, GameVersion $gameVersion): ?GameVersion
    {
        if (!$dungeon->active) {
            return null;
        }

        if ($dungeon->getCurrentMappingVersionForGameVersion($gameVersion) !== null) {
            return $gameVersion;
        }

        $gameVersionIds = $dungeon->loadMappingVersions()->mappingVersions
            ->pluck('game_version_id')
            ->unique()
            ->values()
            ->all();

        return $this->gameVersionRepository->findFirstInDisplayOrder($gameVersionIds);
    }
}
