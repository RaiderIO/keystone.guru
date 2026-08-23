<?php

namespace Tests\Feature\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\EnemyPack;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * TheBlindingValeBridgeRule hardcodes EnemyPack groups 44, 45 and 46, but group numbers come from the MDT import and
 * are only unique per mapping version - a re-import can renumber them. This pins what those three groups contain so
 * that a renumber fails here rather than silently turning the rule into a no-op (or, worse, blocking the wrong packs).
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('TheBlindingValeBridgeRule')]
class TheBlindingValeBridgeRuleMappingTest extends PublicTestCase
{
    /** @var array<int, array<int, int>> The npc_ids each bridge group is expected to hold, sorted */
    private const array EXPECTED_BRIDGE_GROUP_NPC_IDS = [
        44 => [245339, 245339, 245345, 254850],
        45 => [245410, 245410, 245410, 245410, 245410],
        46 => [245346, 245473, 245484],
    ];

    #[Test]
    public function theBlindingValeBridgeEnemyPackGroups_givenTheLatestMappingVersion_stillHoldTheExpectedNpcs(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('key', DungeonKey::THE_BLINDING_VALE->value)->firstOrFail();
        $mappingVersion = $dungeon->mappingVersions()->orderByDesc('version')->firstOrFail();

        foreach (self::EXPECTED_BRIDGE_GROUP_NPC_IDS as $group => $expectedNpcIds) {
            // Act
            $enemyPack = EnemyPack::where('mapping_version_id', $mappingVersion->id)
                ->where('group', $group)
                ->first();

            // Assert
            $this->assertNotNull($enemyPack, sprintf('Bridge EnemyPack group %d no longer exists', $group));

            $npcIds = Enemy::where('enemy_pack_id', $enemyPack->id)
                ->orderBy('npc_id')
                ->pluck('npc_id')
                ->toArray();

            $this->assertEquals(
                $expectedNpcIds,
                $npcIds,
                sprintf('Bridge EnemyPack group %d no longer holds the expected NPCs', $group),
            );
        }
    }
}
