<?php

namespace Tests\Feature\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
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
        'Thundering Totem'       => 135761,
        'Explosive Totem'        => 135764,
        'Torrent Totem'          => 135765,
        'Aka\'ali the Conqueror' => 269808,
        'Zanazal the Wise'       => 269810,
        'Kula the Butcher'       => 269811,
        'Minion of Zul'          => 138493,
        'Shadow of Zul'          => 138489,
        'Reban'                  => 136984,
        'T\'zala'                => 136976,
        'King Dazar'             => 136160,
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
