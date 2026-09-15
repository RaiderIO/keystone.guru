<?php

namespace Tests\Feature\App\Service\CombatLog\ResultEventDungeonRouteService\BFA;

use App\Models\DungeonKey;
use App\Models\Npc\NpcId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\App\Service\CombatLog\ResultEventDungeonRouteService\ResultEventDungeonRouteServiceTestBase;

/**
 * KingsRestDespawningEnemiesRule on the combat-log parsing path (#4275) - the only one of the three rules that
 * awards kills, so it is what proves awardEnemyKills() works on this builder and not just that the rules are
 * notified.
 *
 * The enemies being awarded here despawn instead of dying, so no UNIT_DIED for them is ever logged; the scenarios
 * below therefore only log the neighbours whose deaths do reach us, exactly as a real run would.
 */
#[Group('CombatLog')]
#[Group('ResultEventDungeonRouteService')]
#[Group('KingsRest')]
final class ResultEventDungeonRouteServiceKingsRestTest extends ResultEventDungeonRouteServiceTestBase
{
    /** @var int ui_map_id of floor 44 - the only real floor of the dungeon, floor 458 is a facade */
    private const int UI_MAP_ID_FLOOR_44 = 1004;

    protected function getDungeonKey(): string
    {
        return DungeonKey::KINGS_REST->value;
    }

    protected function getDefaultUiMapId(): int
    {
        return self::UI_MAP_ID_FLOOR_44;
    }

    /**
     * The Council of Tribes is fought against ghosts that vanish when beaten, so the only deaths the encounter ever
     * logs are Zanazal the Wise's totems. The first totem to die awards its two siblings alongside the three
     * bosses, so the whole encounter lands in one pull instead of the later totem deaths spawning more.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenAZanazalTotemDied_awardsTheCouncilOfTribesInTheSamePull(): void
    {
        // Arrange
        $npcKills = [
            $this->npcKill(NpcId::EXPLOSIVE_TOTEM->value, '000008A22F', '21:08:35', '21:08:37', -2911.48, -1075.66),
            $this->npcKill(NpcId::THUNDERING_TOTEM->value, '000008A230', '21:08:41', '21:08:46', -2889.98, -1123.03),
            $this->npcKill(NpcId::TORRENT_TOTEM->value, '000008A231', '21:08:53', '21:08:56', -2933.31, -1122.30),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $this->assertNpcIdsInSamePull($dungeonRoute, [
            NpcId::THUNDERING_TOTEM->value,
            NpcId::EXPLOSIVE_TOTEM->value,
            NpcId::TORRENT_TOTEM->value,
            NpcId::AKAALI_THE_CONQUEROR->value,
            NpcId::ZANAZAL_THE_WISE->value,
            NpcId::KULA_THE_BUTCHER->value,
        ]);
    }

    /**
     * Reban is the last add before King Dazar's encounter, and the encounter itself - King Dazar, T'zala and the
     * Shadow of Zul that has to be past for the party to be here at all - logs no deaths of its own.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenRebanDied_awardsKingDazarsEncounterInTheSamePull(): void
    {
        // Arrange
        $npcKills = [
            $this->npcKill(NpcId::REBAN->value, '0000089D66', '21:11:22', '21:11:46', -3155.99, -966.28),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $this->assertNpcIdsInSamePull($dungeonRoute, [
            NpcId::REBAN->value,
            NpcId::SHADOW_OF_ZUL->value,
            NpcId::TZALA->value,
            NpcId::KING_DAZAR->value,
        ]);
    }

    /**
     * A trigger whose engage resolved to no mapped enemy at all is in no pull, but its death still has to award -
     * the rule marks what it returns as accounted for either way, so dropping the award here would lose the three
     * bosses for the rest of the build rather than postpone them.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenATriggerThatResolvedToNoEnemy_stillAwardsTheCouncilOfTribes(): void
    {
        // Arrange - far outside enemy_engagement_max_range of any mapped Explosive Totem
        $npcKills = [
            $this->npcKill(NpcId::EXPLOSIVE_TOTEM->value, '000008A22F', '21:08:35', '21:08:37', -9999.00, -9999.00),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $this->assertNull(
            $this->findResolvedEnemyMdtId($dungeonRoute, NpcId::EXPLOSIVE_TOTEM->value),
            'The trigger was expected not to resolve to any enemy',
        );
        $this->assertNpcIdsInSamePull($dungeonRoute, [
            NpcId::AKAALI_THE_CONQUEROR->value,
            NpcId::ZANAZAL_THE_WISE->value,
            NpcId::KULA_THE_BUTCHER->value,
        ]);
    }

    /**
     * Nothing is awarded off a death the rule does not key on, so an ordinary trash kill stays a pull of one - which
     * is what makes the two assertions above tests of the rule rather than of the builder awarding blindly.
     */
    #[Test]
    public function convertCombatLogToDungeonRoutes_givenAnNpcTheRuleDoesNotKeyOnDied_awardsNothing(): void
    {
        // Arrange - a Minion of Zul from the early dungeon packs, not the one in the Shadow of Zul's pack
        $npcKills = [
            $this->npcKill(NpcId::MINION_OF_ZUL_EARLY_DUNGEON->value, '0000089D66', '20:48:35', '20:48:36', -2699.27, -972.00),
        ];

        // Act
        $dungeonRoute = $this->buildDungeonRouteFromNpcKills($npcKills);

        // Assert
        $killZones = $this->getKillZones($dungeonRoute);

        $this->assertCount(1, $killZones);
        $this->assertEquals(
            [NpcId::MINION_OF_ZUL_EARLY_DUNGEON->value],
            $killZones->first()->enemies->pluck('npc_id')->all(),
        );
    }
}
