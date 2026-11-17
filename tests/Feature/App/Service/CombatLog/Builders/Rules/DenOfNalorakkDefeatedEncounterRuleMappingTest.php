<?php

namespace Tests\Feature\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Npc\NpcId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * DenOfNalorakkDefeatedEncounterRule awards kills by npc_id, and an award for an npc_id that is not mapped resolves to
 * nothing and disappears into a log line.
 *
 * This pins that the ids the rule names are still part of the mapping, so a re-import that renumbers them fails here
 * instead of quietly turning the rule into a no-op.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('DenOfNalorakkDefeatedEncounterRule')]
class DenOfNalorakkDefeatedEncounterRuleMappingTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('ruleNpcIdProvider')]
    public function npc_givenTheLatestMappingVersion_isStillMapped(NpcId $npcId): void
    {
        // Arrange
        $mappingVersion = Dungeon::where('key', DungeonKey::DEN_OF_NALORAKK->value)
            ->firstOrFail()
            ->mappingVersions()
            ->orderByDesc('version')
            ->firstOrFail();

        // Act
        $mappedNpcIds = Enemy::where('mapping_version_id', $mappingVersion->id)
            ->pluck('npc_id')
            ->unique()
            ->toArray();

        // Assert
        $this->assertContains(
            $npcId->value,
            $mappedNpcIds,
            sprintf('%s (%d) is no longer mapped in Den of Nalorakk', $npcId->name, $npcId->value),
        );
    }

    /**
     * @return array<string, array{NpcId}>
     */
    public static function ruleNpcIdProvider(): array
    {
        return [
            'Nalorakk'         => [NpcId::NALORAKK],
            'Zul\'jarra'       => [NpcId::ZULJARRA],
            'Echo of Nalorakk' => [NpcId::ECHO_OF_NALORAKK],
        ];
    }
}
