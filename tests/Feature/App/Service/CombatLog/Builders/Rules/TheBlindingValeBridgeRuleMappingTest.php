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
 * TheBlindingValeBridgeRule hardcodes the EnemyPack groups on top of the bridge (44, 45, 46) and the ones underneath
 * it (47, 48, 49, 50, 54, 57), but group numbers come from the MDT import and are only unique per mapping version - a
 * re-import can renumber them. This pins what those groups contain so that a renumber fails here rather than silently
 * turning the rule into a no-op (or, worse, blocking the wrong packs).
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('TheBlindingValeBridgeRule')]
class TheBlindingValeBridgeRuleMappingTest extends PublicTestCase
{
    /** @var array<int, array<int, int>> The npc_ids each group the rule names is expected to hold, sorted */
    private const array EXPECTED_BRIDGE_GROUP_NPC_IDS = [
        // On top of the bridge - blocked once Lightwarden Ruia is dead
        44 => [245339, 245339, 245345, 254850],
        45 => [245410, 245410, 245410, 245410, 245410],
        46 => [245346, 245473, 245484],

        // Underneath the bridge - these only spawn once she is dead, so they are blocked until then
        47 => [245410, 245410, 245410, 245410, 245410],
        48 => [245345, 245410, 245410],
        49 => [245346],
        50 => [245336, 245339, 245345, 245345, 245410, 245410, 245410],
        54 => [245345, 245410, 245410, 245410, 245410, 245410],
        57 => [245484],
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
            $this->assertNotNull($enemyPack, sprintf('EnemyPack group %d no longer exists', $group));

            $npcIds = Enemy::where('enemy_pack_id', $enemyPack->id)
                ->orderBy('npc_id')
                ->pluck('npc_id')
                ->toArray();

            $this->assertEquals(
                $expectedNpcIds,
                $npcIds,
                sprintf('EnemyPack group %d no longer holds the expected NPCs', $group),
            );
        }
    }
}
