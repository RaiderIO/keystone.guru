<?php

namespace Tests\Feature\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Npc\NpcId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * TempleOfSethralissDespawningEnemiesRule awards kills by npc_id, and an award for an npc_id that is not mapped
 * resolves to nothing and disappears into a log line.
 *
 * This pins that the ids the rule names are still part of the mapping, so a re-import that renumbers them fails here
 * instead of quietly turning the rule into a no-op.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('TempleOfSethralissDespawningEnemiesRule')]
class TempleOfSethralissDespawningEnemiesRuleMappingTest extends PublicTestCase
{
    #[Test]
    public function staticAnomaly_givenTheLatestMappingVersion_isStillMapped(): void
    {
        // Arrange
        $mappedNpcIds = $this->getLatestMappingVersionNpcIds();

        // Act
        // Assert
        $this->assertContains(
            NpcId::STATIC_ANOMALY->value,
            $mappedNpcIds,
            sprintf('Static Anomaly (%d) is no longer mapped in Temple of Sethraliss', NpcId::STATIC_ANOMALY->value),
        );
    }

    /**
     * The award only ever fires off Galvazzt, so the latest mapping has to carry one of his ids or the anomalies stop
     * being credited entirely.
     */
    #[Test]
    public function galvazzt_givenTheLatestMappingVersion_isStillMappedUnderOneOfHisNpcIds(): void
    {
        // Arrange
        $mappedNpcIds = $this->getLatestMappingVersionNpcIds();

        // Act
        $mappedGalvazztNpcIds = array_intersect(
            [NpcId::GALVAZZT->value, NpcId::GALVAZZT_RESTORED->value],
            $mappedNpcIds,
        );

        // Assert
        $this->assertNotEmpty(
            $mappedGalvazztNpcIds,
            sprintf(
                'Neither Galvazzt npc_id (%d, %d) is mapped in Temple of Sethraliss',
                NpcId::GALVAZZT->value,
                NpcId::GALVAZZT_RESTORED->value,
            ),
        );
    }

    /**
     * The rule triggers on both of Galvazzt's ids, which is only worth doing while routes are still built against the
     * mapping versions that use the older one.
     */
    #[Test]
    public function bothGalvazztNpcIds_givenEveryMappingVersion_areMappedInAtLeastOne(): void
    {
        // Arrange
        $dungeon = $this->getDungeon();

        foreach ([NpcId::GALVAZZT, NpcId::GALVAZZT_RESTORED] as $galvazzt) {
            // Act
            $isMapped = Enemy::whereIn('mapping_version_id', $dungeon->mappingVersions()->pluck('id'))
                ->where('npc_id', $galvazzt->value)
                ->exists();

            // Assert
            $this->assertTrue(
                $isMapped,
                sprintf(
                    '%s (%d) is mapped in no Temple of Sethraliss mapping version at all - the rule need not name it',
                    $galvazzt->name,
                    $galvazzt->value,
                ),
            );
        }
    }

    /**
     * @return array<int, int>
     */
    private function getLatestMappingVersionNpcIds(): array
    {
        $mappingVersion = $this->getDungeon()->mappingVersions()->orderByDesc('version')->firstOrFail();

        return Enemy::where('mapping_version_id', $mappingVersion->id)
            ->pluck('npc_id')
            ->unique()
            ->toArray();
    }

    private function getDungeon(): Dungeon
    {
        return Dungeon::where('key', DungeonKey::TEMPLE_OF_SETHRALISS->value)->firstOrFail();
    }
}
