<?php

namespace Tests\Feature\App\Service\CombatLog\ResultEventDungeonRouteService\BFA;

use App\Models\DungeonKey;
use App\Models\Npc\NpcId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\App\Service\CombatLog\ResultEventDungeonRouteService\ResultEventDungeonRouteServiceTestBase;

/**
 * TempleOfSethralissDespawningEnemiesRule on the combat-log parsing path, which builds routes through
 * ResultEventDungeonRouteBuilder rather than through the Auto Route Creator's request DTOs.
 *
 * Both of this rule's awards have to work on both builders, and the run-finished one especially: it is driven by
 * CHALLENGE_MODE_END rather than by a death, and that is plumbed separately on each path.
 */
#[Group('CombatLog')]
#[Group('ResultEventDungeonRouteService')]
#[Group('TempleOfSethraliss')]
final class ResultEventDungeonRouteServiceTempleOfSethralissTest extends ResultEventDungeonRouteServiceTestBase
{
    /** @var int ui_map_id of the floor Galvazzt is fought on */
    private const int UI_MAP_ID_GALVAZZT_FLOOR = 1038;

    protected function getDungeonKey(): string
    {
        return DungeonKey::TEMPLE_OF_SETHRALISS->value;
    }

    protected function getDefaultUiMapId(): int
    {
        return self::UI_MAP_ID_GALVAZZT_FLOOR;
    }

    /**
     * The Static Anomalies despawn rather than die, and Galvazzt only spawns once they are gone, so his is the only
     * death the encounter logs. All six anomalies are awarded off it, into his pull.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenGalvazztDied_awardsEveryStaticAnomalyInTheSamePull(): void
    {
        // Arrange
        $npcKills = [
            $this->npcKill(NpcId::GALVAZZT_RESTORED->value, '000008A185', '22:35:41', '22:38:04', -3406.31, 3706.55),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $this->assertNpcIdsInSamePull($dungeonRoute, [
            NpcId::GALVAZZT_RESTORED->value,
            NpcId::STATIC_ANOMALY->value,
        ]);
        $this->assertSame(
            6,
            $this->getKillZones($dungeonRoute)
                ->flatMap(static fn($killZone) => $killZone->enemies)
                ->where('npc_id', NpcId::STATIC_ANOMALY->value)
                ->count(),
            'All six mapped Static Anomalies must be credited, not just one',
        );
    }

    /**
     * The Avatar of Sethraliss is won by healing it to full, so it never dies and there is no later death to award it
     * off - completing the dungeon is the only thing that implies it. It lands in a pull of its own.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenTheRunCompleted_awardsTheAvatarOfSethralissInItsOwnPull(): void
    {
        // Arrange
        $npcKills = [
            $this->npcKill(NpcId::GALVAZZT_RESTORED->value, '000008A185', '22:35:41', '22:38:04', -3406.31, 3706.55),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $lastKillZone = $this->getKillZones($dungeonRoute)->last();

        $this->assertEquals(
            [NpcId::AVATAR_OF_SETHRALISS->value],
            $lastKillZone->enemies->pluck('npc_id')->all(),
            'The Avatar of Sethraliss must be the whole of the final pull',
        );
    }

    /**
     * A depleted run never healed the Avatar to full, so it must not be credited - which is what makes the test above
     * a test of the run's outcome rather than of the award firing unconditionally.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenTheRunWasDepleted_doesNotAwardTheAvatarOfSethraliss(): void
    {
        // Arrange
        $npcKills = [
            $this->npcKill(NpcId::GALVAZZT_RESTORED->value, '000008A185', '22:35:41', '22:38:04', -3406.31, 3706.55),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills, success: false);

        // Assert
        $this->assertNotContains(
            NpcId::AVATAR_OF_SETHRALISS->value,
            $this->getKillZones($dungeonRoute)
                ->flatMap(static fn($killZone) => $killZone->enemies)
                ->pluck('npc_id')
                ->all(),
        );
    }
}
