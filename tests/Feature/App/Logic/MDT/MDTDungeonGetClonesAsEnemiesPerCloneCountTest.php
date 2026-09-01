<?php

namespace Tests\Feature\App\Logic\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * A clone may carry its own `count`, superseding its NPC's `count` for that clone alone. MDT 6.2.10 introduced
 * this to give Temple of Sethraliss' G30 different enemy forces than G29, and we read only the NPC-level count,
 * so both groups collapsed to the same value (#4426).
 */
#[Group('UsesLua')]
#[Group('MDT')]
final class MDTDungeonGetClonesAsEnemiesPerCloneCountTest extends PublicTestCase
{
    private const NPC_ID_IMBUED_STORMCALLER = 134599;

    private const NPC_ID_AGITATED_NIMBUS = 136076;

    #[Test]
    public function getClonesAsEnemies_givenMdtClonesCarryingTheirOwnCount_setsEnemyForcesOverrideOnDivergingClonesOnly(): void
    {
        // Arrange
        $dungeon        = Dungeon::query()->where('key', 'templeofsethraliss')->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);

        // Act
        $enemies = $mdtDungeon->getClonesAsEnemies($mappingVersion, $dungeon->floors()->active()->get());

        // Assert - at this stage enemy_pack_id still holds MDT's raw group number, before importEnemyPacks()
        // turns it into real pack membership.
        $this->assertSame(
            [12, 12],
            $this->overridesFor($enemies, self::NPC_ID_IMBUED_STORMCALLER, 30),
            'Both G30 Imbued Stormcallers must take MDT\'s per-clone count of 12.',
        );
        $this->assertSame(
            [null, null],
            $this->overridesFor($enemies, self::NPC_ID_IMBUED_STORMCALLER, 29),
            'G29 Imbued Stormcallers carry no per-clone count, so they must fall through to the NPC-level 7.',
        );
        $this->assertSame(
            [30],
            $this->overridesFor($enemies, self::NPC_ID_AGITATED_NIMBUS, 30),
            'The G30 Agitated Nimbus must take MDT\'s per-clone count of 30.',
        );
        $this->assertSame(
            [null],
            $this->overridesFor($enemies, self::NPC_ID_AGITATED_NIMBUS, 29),
            'The G29 Agitated Nimbus carries no per-clone count, so it must fall through to the NPC-level 25.',
        );
    }

    /**
     * @param Collection<int, Enemy> $enemies
     *
     * @return array<int, int|null>
     */
    private function overridesFor(Collection $enemies, int $npcId, int $mdtGroup): array
    {
        return $enemies
            ->filter(static fn(Enemy $enemy) => $enemy->npc_id === $npcId && $enemy->enemy_pack_id === $mdtGroup)
            ->sortBy('mdt_id')
            ->map(static fn(Enemy $enemy) => $enemy->enemy_forces_override)
            ->values()
            ->all();
    }
}
