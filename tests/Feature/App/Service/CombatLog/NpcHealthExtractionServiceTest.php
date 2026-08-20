<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use App\Service\CombatLog\Dtos\DataExtraction\NpcHealthObservation;
use App\Service\CombatLog\NpcHealthExtractionServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('NpcHealthExtractionService')]
final class NpcHealthExtractionServiceTest extends PublicTestCase
{
    private NpcHealthExtractionServiceInterface $service;

    private GameVersion $gameVersion;

    private Dungeon $dungeon;

    /** A non-boss NPC of $dungeon that has a real (non-placeholder) health row for $gameVersion */
    private Npc $npc;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service     = app(NpcHealthExtractionServiceInterface::class);
        $this->gameVersion = GameVersion::getDefaultGameVersion();

        // Any seeded dungeon with a mapped non-boss NPC that carries a real health value will do
        $npcHealth = NpcHealth::query()
            ->where('game_version_id', $this->gameVersion->id)
            ->where('health', '>', NpcHealth::HEALTH_PLACEHOLDER)
            ->whereNull('percentage')
            ->whereHas('npc', static function ($query) {
                $query->whereHas('dungeons')->where('classification_id', '<', 3);
            })
            ->with(['npc.dungeons', 'npc.npcHealths'])
            ->firstOrFail();

        $this->npc     = $npcHealth->npc;
        $this->dungeon = $this->npc->dungeons->first();
        $this->assertFalse($this->npc->isBoss());
    }

    #[Test]
    public function compareNpcHealths_givenObservationOfDungeonNpc_reversesTheScalingFactor(): void
    {
        // Arrange - an NPC seen at +2 with twice the health the factor predicts for its stored base
        $existingHealth = $this->npc->getHealthByGameVersion($this->gameVersion)->health;
        $factor         = $this->npc->getScalingFactor(2, []);
        $observations   = $this->observations($this->npc->id, 2, (int)round($existingHealth * 2 * $factor));

        // Act
        $changes = $this->service->compareNpcHealths($observations, $this->dungeon, $this->gameVersion);

        // Assert
        $this->assertCount(1, $changes);
        $change = $changes->get($this->npc->id);
        $this->assertSame($factor, $change->scalingFactor);
        $this->assertEqualsWithDelta($existingHealth * 2, $change->newHealth, 1);
        $this->assertSame($change->newHealth, $change->observedBaseHealth);
        $this->assertFalse($change->isMissing());
        $this->assertFalse($change->isPlaceholder());
        $this->assertEqualsWithDelta(100.0, $change->getDeltaPercent(), 0.01);
        $this->assertFalse($change->shouldWrite(false));
        $this->assertTrue($change->shouldWrite(true));
    }

    #[Test]
    public function compareNpcHealths_givenObservationMatchingTheStoredHealth_isUnchanged(): void
    {
        // Arrange
        $existingHealth = $this->npc->getHealthByGameVersion($this->gameVersion)->health;
        $observations   = $this->observations($this->npc->id, 5, (int)round($existingHealth * $this->npc->getScalingFactor(5, [])));

        // Act
        $change = $this->service->compareNpcHealths($observations, $this->dungeon, $this->gameVersion)->get($this->npc->id);

        // Assert
        $this->assertTrue($change->isUnchanged());
        $this->assertFalse($change->shouldWrite(true));
    }

    #[Test]
    public function compareNpcHealths_givenNpcNotInDungeon_ignoresIt(): void
    {
        // Arrange - an NPC id that no dungeon maps
        $observations = $this->observations(999_999_999, 2, 1_000_000);

        // Act
        $changes = $this->service->compareNpcHealths($observations, $this->dungeon, $this->gameVersion);

        // Assert
        $this->assertTrue($changes->isEmpty());
    }

    #[Test]
    public function compareNpcHealths_givenSameNpcAtTwoKeyLevels_usesTheLowestKeyLevel(): void
    {
        // Arrange
        $observations = $this->observations($this->npc->id, 10, 10_000_000)
            ->merge($this->observations($this->npc->id, 2, 1_000_000));

        // Act
        $change = $this->service->compareNpcHealths($observations, $this->dungeon, $this->gameVersion)->get($this->npc->id);

        // Assert
        $this->assertSame(2, $change->observation->keyLevel);
    }

    #[Test]
    public function compareNpcHealths_givenRowWithPercentage_newHealthRoundTripsThroughCalculateHealthForKey(): void
    {
        // Arrange - temporarily give the row a percentage
        $npcHealth = $this->npc->getHealthByGameVersion($this->gameVersion);
        $original  = ['health' => $npcHealth->health, 'percentage' => $npcHealth->percentage];

        try {
            $npcHealth->update(['percentage' => 40]);
            $this->refreshNpc();
            $observedMaxHp = 4_000_000;
            $observations  = $this->observations($this->npc->id, 6, $observedMaxHp);

            // Act
            $change = $this->service->compareNpcHealths($observations, $this->dungeon, $this->gameVersion)->get($this->npc->id);

            // Assert - health * 40% * factor must give back what the log showed
            $this->assertSame((int)round($observedMaxHp / $change->scalingFactor), $change->observedBaseHealth);
            $this->assertSame((int)round($change->observedBaseHealth * 100 / 40), $change->newHealth);

            $npcHealth->update(['health' => $change->newHealth]);
            $this->refreshNpc();
            $this->assertEqualsWithDelta($observedMaxHp, $this->npc->calculateHealthForKey($this->gameVersion, 6, []), 3);
        } finally {
            $npcHealth->update($original);
            $this->flushModelCaches();
        }
    }

    #[Test]
    public function applyNpcHealths_givenPlaceholderRow_writesWithoutOverwrite(): void
    {
        // Arrange
        $npcHealth = $this->npc->getHealthByGameVersion($this->gameVersion);
        $original  = ['health' => $npcHealth->health, 'percentage' => $npcHealth->percentage];

        try {
            $npcHealth->update(['health' => NpcHealth::HEALTH_PLACEHOLDER]);
            $this->refreshNpc();
            $changes = $this->service->compareNpcHealths($this->observations($this->npc->id, 2, 2_140_000), $this->dungeon, $this->gameVersion);
            $this->assertTrue($changes->get($this->npc->id)->isPlaceholder());

            // Act
            $written = $this->service->applyNpcHealths($changes, $this->gameVersion, false);

            // Assert
            $this->assertSame(1, $written);
            $this->assertSame(
                $changes->get($this->npc->id)->newHealth,
                $this->storedHealth($this->gameVersion),
            );
        } finally {
            NpcHealth::query()->where('npc_id', $this->npc->id)->where('game_version_id', $this->gameVersion->id)->update($original);
            $this->flushModelCaches();
        }
    }

    #[Test]
    public function applyNpcHealths_givenRealRow_writesOnlyWithOverwrite(): void
    {
        // Arrange
        $npcHealth = $this->npc->getHealthByGameVersion($this->gameVersion);
        $original  = ['health' => $npcHealth->health, 'percentage' => $npcHealth->percentage];
        $changes   = $this->service->compareNpcHealths($this->observations($this->npc->id, 2, 2_140_000), $this->dungeon, $this->gameVersion);
        $this->assertFalse($changes->get($this->npc->id)->isUnchanged());

        try {
            // Act
            $writtenWithoutOverwrite = $this->service->applyNpcHealths($changes, $this->gameVersion, false);
            $healthWithoutOverwrite  = $this->storedHealth($this->gameVersion);
            $writtenWithOverwrite    = $this->service->applyNpcHealths($changes, $this->gameVersion, true);
            $healthWithOverwrite     = $this->storedHealth($this->gameVersion);

            // Assert
            $this->assertSame(0, $writtenWithoutOverwrite);
            $this->assertSame($original['health'], $healthWithoutOverwrite);
            $this->assertSame(1, $writtenWithOverwrite);
            $this->assertSame($changes->get($this->npc->id)->newHealth, $healthWithOverwrite);
        } finally {
            NpcHealth::query()->where('npc_id', $this->npc->id)->where('game_version_id', $this->gameVersion->id)->update($original);
            $this->flushModelCaches();
        }
    }

    #[Test]
    public function applyNpcHealths_givenMissingRow_createsIt(): void
    {
        // Arrange - pick a game version the NPC has no row for
        $otherGameVersion = GameVersion::query()->where('id', '!=', $this->gameVersion->id)->firstOrFail();
        $this->assertNull($this->npc->getHealthByGameVersion($otherGameVersion));
        $changes = $this->service->compareNpcHealths($this->observations($this->npc->id, 2, 2_140_000), $this->dungeon, $otherGameVersion);
        $this->assertTrue($changes->get($this->npc->id)->isMissing());

        try {
            // Act
            $written = $this->service->applyNpcHealths($changes, $otherGameVersion, false);

            // Assert
            $this->assertSame(1, $written);
            $this->assertSame(
                $changes->get($this->npc->id)->newHealth,
                $this->storedHealth($otherGameVersion),
            );
        } finally {
            NpcHealth::query()->where('npc_id', $this->npc->id)->where('game_version_id', $otherGameVersion->id)->delete();
            $this->flushModelCaches();
        }
    }

    /**
     * Model caching is on in CI (off in local dev), and the eager-loaded npcHealths of $dungeon->npcs() are cached
     * under the Npc model - so every mutation in these tests must flush both before anything re-reads them.
     */
    private function flushModelCaches(): void
    {
        new Npc()->flushCache();
        new NpcHealth()->flushCache();
    }

    private function refreshNpc(): void
    {
        $this->flushModelCaches();
        $this->npc->load('npcHealths');
    }

    private function storedHealth(GameVersion $gameVersion): ?int
    {
        $this->flushModelCaches();

        return NpcHealth::query()->where('npc_id', $this->npc->id)->where('game_version_id', $gameVersion->id)->value('health');
    }

    /**
     * @return Collection<string, NpcHealthObservation>
     */
    private function observations(int $npcId, int $keyLevel, int $maxHp): Collection
    {
        $observation = new NpcHealthObservation($npcId, $this->dungeon->id, $keyLevel, []);
        $observation->addSample($maxHp);

        return collect([sprintf('%d-%d', $npcId, $keyLevel) => $observation]);
    }
}
