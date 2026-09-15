<?php

namespace Tests\Feature\App\Service\CombatLog\ResultEventDungeonRouteService\Midnight;

use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\App\Service\CombatLog\ResultEventDungeonRouteService\ResultEventDungeonRouteServiceTestBase;

/**
 * TheBlindingValeBridgeRule on the combat-log parsing path (#4275). The scenarios mirror
 * APICombatLogControllerCombatLogRouteTheBlindingValeTest exactly, down to the ingame positions, so that the two
 * builders can be compared on identical input.
 */
#[Group('CombatLog')]
#[Group('ResultEventDungeonRouteService')]
#[Group('TheBlindingVale')]
final class ResultEventDungeonRouteServiceTheBlindingValeTest extends ResultEventDungeonRouteServiceTestBase
{
    /** @var int Meittik - one of the three first bosses, fought at the near end of the bridge */
    private const int NPC_ID_MEITTIK = 243028;

    /** @var int Ikuzz the Light Hunter, the second boss */
    private const int NPC_ID_IKUZZ = 244887;

    /** @var int Lightwarden Ruia, the third boss - the party only walks underneath the bridge once she is dead */
    private const int NPC_ID_LIGHTWARDEN_RUIA = 245912;

    /** @var int Mapped both in bridge group 46 and in group 49 just below it, which is what makes it useful here */
    private const int NPC_ID_GROVEKEEPER = 245346;

    /** @var int mdt_id of the NPC_ID_GROVEKEEPER enemy in bridge group 46, at ingame -1712.32/1324.16 */
    private const int MDT_ID_GROVEKEEPER_ON_BRIDGE = 5;

    /** @var int mdt_id of the closest NPC_ID_GROVEKEEPER enemy outside the bridge groups, in group 49 */
    private const int MDT_ID_GROVEKEEPER_OFF_BRIDGE = 6;

    /** @var int ui_map_id of floor 408 - the only real floor of the dungeon, floor 459 is a facade */
    private const int UI_MAP_ID_FLOOR_408 = 2500;

    protected function getDungeonKey(): string
    {
        return DungeonKey::THE_BLINDING_VALE->value;
    }

    protected function getDefaultUiMapId(): int
    {
        return self::UI_MAP_ID_FLOOR_408;
    }

    /**
     * The bridge and the path underneath it share floor 408, near identical ingame X/Y and their npc_ids, so nothing
     * spatial tells them apart. Once Lightwarden Ruia is dead the party is past the bridge for good, so a kill
     * logged right on top of a bridge enemy must resolve to something off the bridge instead.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenAnNpcKilledOnTheBridgeAfterLightwardenRuiaDied_doesNotResolveItToABridgeEnemy(): void
    {
        // Arrange
        $npcKills = [
            $this->npcKill(self::NPC_ID_MEITTIK, '000014B001', '20:43:28', '20:45:32', -1741.79, 1491.81),
            $this->npcKill(self::NPC_ID_IKUZZ, '000014B002', '20:46:47', '20:49:11', -1737.49, 1227.90),
            $this->npcKill(self::NPC_ID_LIGHTWARDEN_RUIA, '000014B003', '20:50:47', '20:53:03', -1339.87, 1351.59),
            $this->npcKill(self::NPC_ID_GROVEKEEPER, '000014B004', '20:56:40', '20:56:45', -1712.00, 1324.00),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $this->assertEquals(
            self::MDT_ID_GROVEKEEPER_OFF_BRIDGE,
            $this->findResolvedEnemyMdtId($dungeonRoute, self::NPC_ID_GROVEKEEPER),
        );
    }

    /**
     * The same kill before Lightwarden Ruia dies still resolves to the bridge enemy it is standing on - which is what
     * makes the assertion above a test of the rule and not of the spatial matching.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenAnNpcKilledOnTheBridgeBeforeLightwardenRuiaDied_resolvesItToTheBridgeEnemy(): void
    {
        // Arrange
        $npcKills = [
            $this->npcKill(self::NPC_ID_MEITTIK, '000014B001', '20:43:28', '20:45:32', -1741.79, 1491.81),
            $this->npcKill(self::NPC_ID_IKUZZ, '000014B002', '20:46:47', '20:49:11', -1737.49, 1227.90),
            $this->npcKill(self::NPC_ID_GROVEKEEPER, '000014B004', '20:56:40', '20:56:45', -1712.00, 1324.00),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $this->assertEquals(
            self::MDT_ID_GROVEKEEPER_ON_BRIDGE,
            $this->findResolvedEnemyMdtId($dungeonRoute, self::NPC_ID_GROVEKEEPER),
        );
    }
}
