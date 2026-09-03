<?php

namespace Tests\Feature\App\Service\CombatLog\ResultEventDungeonRouteService\Midnight;

use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\App\Service\CombatLog\ResultEventDungeonRouteService\ResultEventDungeonRouteServiceTestBase;

/**
 * BossKillFloorCutoffRule on the combat-log parsing path (#4275). The #4140 cutoff never applied here - not even
 * before #4272 moved it into Rules/ - so this is the first coverage it has on this builder at all. The scenarios
 * mirror APICombatLogControllerCombatLogRouteVoidscarArenaTest, down to the ingame positions.
 */
#[Group('CombatLog')]
#[Group('ResultEventDungeonRouteService')]
#[Group('VoidscarArena')]
final class ResultEventDungeonRouteServiceVoidscarArenaTest extends ResultEventDungeonRouteServiceTestBase
{
    /** @var int Taz'Rah - the first boss, mapped on floor 455 (index 2) */
    private const int NPC_ID_TAZRAH = 238887;

    /** @var int NPC 243988 is mapped on all three Voidscar floors, which is what makes it useful here */
    private const int NPC_ID_STACKED_TRASH = 243988;

    /** @var int mdt_id of the NPC_ID_STACKED_TRASH enemy on floor 454 (index 1), at ingame 482.70/4443.30 */
    private const int MDT_ID_TRASH_BEFORE_BOSS_FLOOR = 11;

    /** @var int mdt_id of the closest NPC_ID_STACKED_TRASH enemy on floor 455 (index 2), ~94 yards away */
    private const int MDT_ID_TRASH_ON_BOSS_FLOOR = 2;

    /** @var int ui_map_id of floor 455 - where both the boss and the trash kill below are logged */
    private const int UI_MAP_ID_FLOOR_455 = 2572;

    protected function getDungeonKey(): string
    {
        return DungeonKey::VOIDSCAR_ARENA->value;
    }

    protected function getDefaultUiMapId(): int
    {
        return self::UI_MAP_ID_FLOOR_455;
    }

    /**
     * Killing Taz'Rah (floor 455, index 2) must take floor 454's enemies out of the running for everything that is
     * killed afterwards. NPC 243988 is mapped on all three floors, so a kill logged right on top of its floor-454
     * enemy only ever resolves to the floor-455 one because of the cutoff.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenAnNpcKilledOnAStackedFloorAfterABossDied_doesNotResolveItToAnEnemyBeforeTheBossFloor(): void
    {
        // Arrange
        $npcKills = [
            $this->npcKill(self::NPC_ID_TAZRAH, '000004BD4D', '20:15:16', '20:17:38', 451.78, 4535.17),
            $this->npcKill(self::NPC_ID_STACKED_TRASH, '000004BE99', '20:19:00', '20:19:30', 482.70, 4443.30),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $this->assertEquals(
            self::MDT_ID_TRASH_ON_BOSS_FLOOR,
            $this->findResolvedEnemyMdtId($dungeonRoute, self::NPC_ID_STACKED_TRASH),
        );
    }

    /**
     * The same kill without the preceding boss death still resolves to the floor-454 enemy it is standing on - which
     * is what makes the assertion above a test of the cutoff and not of the spatial matching.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenAnNpcKilledOnAStackedFloorWithoutABossDying_resolvesItToTheClosestEnemy(): void
    {
        // Arrange
        $npcKills = [
            $this->npcKill(self::NPC_ID_STACKED_TRASH, '000004BE99', '20:19:00', '20:19:30', 482.70, 4443.30),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $this->assertEquals(
            self::MDT_ID_TRASH_BEFORE_BOSS_FLOOR,
            $this->findResolvedEnemyMdtId($dungeonRoute, self::NPC_ID_STACKED_TRASH),
        );
    }
}
