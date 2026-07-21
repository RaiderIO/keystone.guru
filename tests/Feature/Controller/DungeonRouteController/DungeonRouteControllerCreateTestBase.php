<?php

namespace Tests\Feature\Controller\DungeonRouteController;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
abstract class DungeonRouteControllerCreateTestBase extends PublicTestCase
{
    protected function latestRouteSince(int $sinceId): ?DungeonRoute
    {
        return DungeonRoute::query()
            ->where('id', '>', $sinceId)
            ->orderByDesc('id')
            ->first();
    }

    protected function getActiveDungeon(): Dungeon
    {
        return Dungeon::query()
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->where('dungeons.speedrun_enabled', false)
            ->select('dungeons.*')
            ->firstOrFail();
    }

    protected function getActiveSpeedrunDungeon(): Dungeon
    {
        $dungeon = Dungeon::query()
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->where('dungeons.speedrun_enabled', true)
            ->select('dungeons.*')
            ->with('dungeonSpeedrunDifficulties')
            ->get()
            ->first(static fn(Dungeon $dungeon): bool => count($dungeon->getEnabledSpeedrunDifficulties()) >= 2);

        $this->assertNotNull($dungeon, 'Expected a speedrun dungeon with at least two enabled difficulties.');

        return $dungeon;
    }

    protected function firstDifficultyNotEnabledFor(Dungeon $dungeon): int
    {
        $enabled = $dungeon->getEnabledSpeedrunDifficulties();
        // A valid enum difficulty that is not one of this dungeon's enabled speedrun difficulties
        $notEnabled = collect(array_values(Dungeon::DIFFICULTY_ALL))
            ->first(static fn(int $difficulty): bool => !in_array($difficulty, $enabled, true));

        $this->assertNotNull($notEnabled, 'Expected a valid difficulty that is not enabled for this dungeon.');

        return (int)$notEnabled;
    }

    protected function getInactiveDungeon(): Dungeon
    {
        return Dungeon::query()->where('active', false)->firstOrFail();
    }

    protected function getFactionSelectionRequiredDungeon(): Dungeon
    {
        return Dungeon::factionSelectionRequired()->where('active', true)->firstOrFail();
    }

    /**
     * @return array{0: Dungeon, 1: MapIcon}
     */
    protected function getDungeonWithStartIcon(): array
    {
        // Some mapping versions are "bare" (e.g. created only to hold floor union data) and don't
        // have a cloned dungeon start icon, so the newest icon overall isn't necessarily one that
        // lives on its own dungeon's current mapping version - walk candidates until one does.
        $mapIcons = MapIcon::query()
            ->where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START])
            ->whereNotNull('mapping_version_id')
            ->whereHas('mappingVersion.dungeon', static function ($query): void {
                $query->where('active', true);
            })
            ->with('mappingVersion.dungeon')
            ->orderByDesc('id')
            ->get();

        foreach ($mapIcons as $mapIcon) {
            $dungeon = $mapIcon->mappingVersion->dungeon;

            if ($dungeon->getCurrentMappingVersion()?->id === $mapIcon->mapping_version_id) {
                return [$dungeon, $mapIcon];
            }
        }

        $this->fail('Expected a seeded dungeon start map icon on its dungeon\'s current mapping version.');
    }
}
