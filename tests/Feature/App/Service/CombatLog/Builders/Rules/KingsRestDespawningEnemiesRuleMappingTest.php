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
 * KingsRestDespawningEnemiesRule awards kills by npc_id, and an award for an npc_id that is not mapped resolves to
 * nothing and disappears into a log line. Several of these npcs also exist under a second id carrying the same name
 * (the Council of Tribes, the Minion of Zul), so picking the wrong one fails silently rather than loudly.
 *
 * This pins that every id the rule names is still part of King's Rest' latest mapping, so a re-import that renumbers
 * them fails here instead of quietly turning the rule into a no-op.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('KingsRestDespawningEnemiesRule')]
class KingsRestDespawningEnemiesRuleMappingTest extends PublicTestCase
{
    /** @var array<string, int> Every npc_id the rule triggers on or awards a kill for */
    private const array EXPECTED_NPC_IDS = [
        'Thundering Totem'       => NpcId::THUNDERING_TOTEM->value,
        'Explosive Totem'        => NpcId::EXPLOSIVE_TOTEM->value,
        'Torrent Totem'          => NpcId::TORRENT_TOTEM->value,
        'Aka\'ali the Conqueror' => NpcId::AKAALI_THE_CONQUEROR->value,
        'Zanazal the Wise'       => NpcId::ZANAZAL_THE_WISE->value,
        'Kula the Butcher'       => NpcId::KULA_THE_BUTCHER->value,
        'Minion of Zul'          => NpcId::MINION_OF_ZUL->value,
        'Shadow of Zul'          => NpcId::SHADOW_OF_ZUL->value,
        'Reban'                  => NpcId::REBAN->value,
        'T\'zala'                => NpcId::TZALA->value,
        'King Dazar'             => NpcId::KING_DAZAR->value,
    ];

    #[Test]
    public function kingsRestDespawningEnemies_givenTheLatestMappingVersion_areAllStillMapped(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('key', DungeonKey::KINGS_REST->value)->firstOrFail();
        $mappingVersion = $dungeon->mappingVersions()->orderByDesc('version')->firstOrFail();

        // Act
        $mappedNpcIds = Enemy::where('mapping_version_id', $mappingVersion->id)
            ->pluck('npc_id')
            ->unique()
            ->toArray();

        // Assert
        foreach (self::EXPECTED_NPC_IDS as $name => $npcId) {
            $this->assertContains(
                $npcId,
                $mappedNpcIds,
                sprintf('%s (%d) is no longer mapped in King\'s Rest', $name, $npcId),
            );
        }
    }
}
