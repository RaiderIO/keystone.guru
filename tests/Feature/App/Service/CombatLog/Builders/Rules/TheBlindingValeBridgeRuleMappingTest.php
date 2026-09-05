<?php

namespace Tests\Feature\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Npc\NpcId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * TheBlindingValeBridgeRule hardcodes the EnemyPack groups on top of the bridge (44, 45, 46) and the ones underneath
 * it (47, 48, 49, 50, 54), but group numbers come from the MDT import and are only unique per mapping version - a
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
        44 => [NpcId::UNDERBRUSH_STALKER->value, NpcId::UNDERBRUSH_STALKER->value, NpcId::LIGHTGORGED_LASHER->value, NpcId::SPOREBLIGHT_BELCHER->value],
        45 => [NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value],
        46 => [NpcId::VIRID_GROVEKEEPER->value, NpcId::THORNY_SAPTOR->value, NpcId::LIGHTFEATHER_PETALWING->value],

        // Underneath the bridge - these only spawn once she is dead, so they are blocked until then
        47 => [NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value],
        48 => [NpcId::LIGHTGORGED_LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value],
        49 => [NpcId::VIRID_GROVEKEEPER->value],
        50 => [NpcId::RADIANT_SPELLSOWER->value, NpcId::UNDERBRUSH_STALKER->value, NpcId::LIGHTGORGED_LASHER->value, NpcId::LIGHTGORGED_LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value],
        54 => [NpcId::LIGHTGORGED_LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value, NpcId::LASHER->value],
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
