<?php

namespace Tests\Feature\Controller\DungeonRouteController;

use App\Models\Dungeon;
use App\Models\DungeonDifficulty;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonStart;
use PHPUnit\Framework\Attributes\Group;
use Tests\Fixtures\Traits\CreatesNpclessCombatLogDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
abstract class DungeonRouteControllerCreateTestBase extends PublicTestCase
{
    use CreatesNpclessCombatLogDungeon;

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

    protected function getActiveDungeonOtherThan(Dungeon $excludedDungeon): Dungeon
    {
        return Dungeon::query()
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->where('dungeons.speedrun_enabled', false)
            ->where('dungeons.id', '!=', $excludedDungeon->id)
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
        $notEnabled = collect(DungeonDifficulty::values())
            ->first(static fn(int $difficulty): bool => !in_array($difficulty, $enabled, true));

        $this->assertNotNull($notEnabled, 'Expected a valid difficulty that is not enabled for this dungeon.');

        return (int)$notEnabled;
    }

    protected function getInactiveDungeon(int $mapId, string $key): Dungeon
    {
        return $this->createDungeonWithoutNpcs($mapId, $key);
    }

    /**
     * `CreatesNpclessCombatLogDungeon::deleteDungeon()` is private to the trait, so subclasses
     * calling {@see getInactiveDungeon()} need this wrapper to clean up.
     */
    protected function cleanupInactiveDungeon(?Dungeon $dungeon): void
    {
        $this->deleteDungeon($dungeon);
    }

    protected function getFactionSelectionRequiredDungeon(): Dungeon
    {
        return Dungeon::factionSelectionRequired()->where('active', true)->firstOrFail();
    }

    /**
     * @return array{0: Dungeon, 1: DungeonStart}
     */
    protected function getDungeonWithDungeonStart(): array
    {
        // Some mapping versions are "bare" (e.g. created only to hold floor union data) and don't
        // have a cloned dungeon start, so the newest start overall isn't necessarily one that
        // lives on its own dungeon's current mapping version - walk candidates until one does.
        $dungeonStarts = DungeonStart::query()
            ->whereHas('mappingVersion.dungeon', static function ($query): void {
                $query->where('active', true);
            })
            ->with('mappingVersion.dungeon')
            ->orderByDesc('id')
            ->get();

        foreach ($dungeonStarts as $dungeonStart) {
            $dungeon = $dungeonStart->mappingVersion->dungeon;

            if ($dungeon->getCurrentMappingVersion()?->id === $dungeonStart->mapping_version_id) {
                return [$dungeon, $dungeonStart];
            }
        }

        $this->fail('Expected a seeded dungeon start on its dungeon\'s current mapping version.');
    }
}
